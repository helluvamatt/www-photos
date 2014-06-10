<?php

$app = require '../bootstrap.php';

use Schneenet\Photos\Models;

// special routing middleware that redirects if the user is not logged in
$isAuth = function(\Slim\Route $route)
{
	$app = \Slim\Slim::getInstance();
	if (!isset($app->user))
	{
		$app->flash('redirect', $route->getName());
		$app->flash('error', "You must be logged in to do that.");
		$app->redirect($app->urlFor('/login'), 302);
	}
};

// the following two routes will acheive the same thing
function route_browse()
{
	$app = \Slim\Slim::getInstance();
	$data = array(
		'current_page' => '/browse',
	);
	$app->render("browse.twig", $data);
}
$app->get('/', 'route_browse')->name('/');
$app->get('/browse', 'route_browse')->name('/browse');

$app->post('/login', function() use ($app) {
	// Do login
	$user = $app->request->post('user');
	$redirect = $app->request->post('redirect');
	$user_obj = Models\User::query()->where('username', $user['username'])->first();
	if ($user_obj != null && $user_obj->password != '' && password_verify($user['password'], $user_obj->password))
	{
		$_SESSION['user'] = $user_obj->id;
		if ($redirect == '') { $redirect = '/browse'; }
		$app->response->redirect(\Schneenet\DefaultViewParams::createUrlFor($app, $redirect), 302);
	}
	else
	{
		$app->flashNow('redirect', $redirect);
		$app->flashNow('error', "Username or password invalid. Please check them and try again.");
		$view_data['username'] = $user['username'];
		$app->render("login.twig", $view_data);
	}
})->name('/login');
$app->get('/login', function() use ($app) {
	$app->render("login.twig");
})->name('GET/login');

$app->get('/logout', function() use ($app) {
	unset($_SESSION['user']);
	$app->response->redirect($app->urlFor('/'), 302);
})->name('/logout');

/* *************************************************************************** */
/* Public Profiles                                                             */
/* *************************************************************************** */
$app->get('/profile/:username', function($username) use ($app) {
	
	$data = array();
	$user = Models\User::with('profile_values')->where('username', $username)->first();
	if ($user != null)
	{
		if ($app->user != null && $user->username == $app->user->username)
		{
			$data['current_page'] = '/profile/:current';
		}
		$data['profile_user'] = $user;
		$data['profile'] = $user->profile_map($app->user);
		$data['profile_picture'] = $user->profile_picture;
		$app->render("profile.twig", $data);
	}
	else
	{
		$app->notFound();
	}
})->name('/profile/:username');

$app->get('/profile/:username/albums', function($username) use ($app) {
	$data = array();
	$user = Models\User::with('albums', 'albums.photos')->where('username', $username)->first();
	if ($user != null)
	{
		if ($app->user != null && $user->username == $app->user->username)
		{
			$data['current_page'] = '/profile/:current/albums';
		}
		$data['title'] = $user->display_name() . "'s Albums";
		$data['albums'] = array_filter($user->albums->all(), function($album) use ($app) {
			// Filter by permissions
			return $album->userCanSee($app->user);
		});
		$app->render("browse.twig", $data);
	}
	else
	{
		$app->notFound();
	}
})->name('/profile/:username/albums');

$app->get('/profile/:username/photos', function($username) use ($app) {
	$user = Models\User::with('photos')->where('username', $username)->first();
	if ($user != null)
	{
		$data = array();
		if ($app->user != null && $user->username == $app->user->username)
		{
			$data['current_page'] = '/profile/:current/photos';
		}
		$data['title'] = $user->display_name() . "'s Photos";
		$data['photos'] = array_filter($user->photos->all(), function($photo) use ($app) {
			// Filter by permissions
			return $photo->userCanSee($app->user);
		});
		$app->render("browse.twig", $data);
	}
	else
	{
		$app->notFound();
	}
})->name('/profile/:username/photos');

/* *************************************************************************** */
/* Album Viewer/Editor                                                         */
/* *************************************************************************** */

// TODO Make this like how GMail handles it, upload the image as soon as it is chosen and dynamically redraw the album view

$app->get('/album/:id', function($id) use ($app) {
	
	// Album viewer
	$album = Models\Album::with('photos')->find($id);
	if (isset($album))
	{
		$data = array();
		$data['title'] = $album->title;
		$data['album_id'] = $album->id;
		$data['photos'] = array_filter($album->photos->all(), function($photo) use ($app) {
			// Filter by permissions
			return $photo->userCanSee($app->user);
		});
		if (isset($app->user) && $album->owner->id == $app->user->id)
		{
			$data['editor_mode'] = true;
		}
		else
		{
			$data['debug'] = $album;
		}
		$app->render('album.twig', $data);
	}
	else
	{
		$app->notFound();
	}
	
})->name('/album/:id');

$app->post('/api/album/photos', function() use ($app) {
	
	$album_id = $app->request->post('album_id');
	$start = $app->request->post('start');
	$count = $app->request->post('count');
	
	$body = '';
	
	$album = Models\Album::with(array('photos' => function($q) use ($start, $count) {
		$q->skip($start)->take($count)->orderBy('album_order', 'created_at');
	}))->find($album_id);
	
	if (isset($album))
	{
		if ($album->photos->count() > 0)
		{
			$photos = array_filter($album->photos->all(), function($photo) use ($app) {
				// Filter by permissions
				return $photo->userCanSee($app->user);
			});
			$photos_json = array();
			foreach ($photos as $photo)
			{
				$imgUrl = $app->urlFor('/img/:id', array('id' => $photo->id . "." . $photo->type));
				$thumbUrl = $app->urlFor('/thumb/:id', array('id' => $photo->id . "." . $photo->type));
				$photos_json[] = json_encode(array('id' => $photo->id, 'url' => $imgUrl, 'thumbUrl' => $thumbUrl));
			}
			$body = '{"photos":[' . implode(',', $photos_json) . ']}';
		}
		else 
		{
			$body = json_encode(array('photos' => array(), 'noMore' => 1));
		}
	}
	else 
	{
		$body = json_encode(array('error' => "Album not found."));
	}
	
	$app->response->headers->set('Content-Type', 'application/json');
	$app->response->setBody($body);
	$app->log->info('Processed Upload: ' . $body);
	
})->name('POST/api/album/photos');

$app->post('/api/upload', function() use ($app) {
	
	// Handle uploads asynchronously, the album viewer will handle redrawing itself to show newly uploaded images
	$res = array();
	
	if (isset($app->user))
	{
		$valid_types = '/(\.|\/)(gif|jpe?g|png)$/i';
		$max_size = 5 * 1024 * 1024;
		if (isset($_FILES['files']) && $_FILES['files']['error'][0] === 0)
		{
			$tmp_name = $_FILES['files']['tmp_name'][0];
			$type = Models\Photo::getMimeType($tmp_name);
			$size = $_FILES['files']['size'][0];
			if ($size <= $max_size)
			{
				if (preg_match($valid_types, $type, $m))
				{
					$album_id = $app->request->post('album_id');
					$album = Models\Album::find($album_id);
					if (isset($album))
					{
						$id = Models\Photo::createId();
						$photo = new Models\Photo();
						$photo->id = $id;
						$photo->album()->associate($album);
						$photo->owner()->associate($app->user);
						$photo->type = $m[2];
						$photo->save();
						$base_path = str_replace("%BASEDIR%", BASEDIR, $app->config['photos_path']);
						$original_path = $photo->getOriginalPath($base_path);
						$thumb_path = $photo->getThumbPath($base_path);
						$viewer_path = $photo->getPath($base_path); 
						$dir = dirname($original_path);
						if (mkdir($dir, 0755, true) && move_uploaded_file($tmp_name, $original_path))
						{
							
							\Schneenet\Image::createThumbnail($original_path, $thumb_path, $photo->type, 200);
							\Schneenet\Image::createThumbnail($original_path, $viewer_path, $photo->type, 1000);
							
							$imgId = $photo->id . '.' . $photo->type;
							$res['photo'] = array(
								'id' => $id,
								'url' => $app->urlFor('/thumb/:id', array('id' => $imgId)),
							);
						}
						else
						{
							$res['error'] = "Upload failed. Please try again. [501]";
						}
					}
					else 
					{
						$res['error'] = "Upload failed. Album not found. [505]";
					}
				}
				else
				{
					$res['error'] = "Upload failed. Not an image file. [502]";
				}
			}
			else
			{
				$res['error'] = "Upload failed. File greater than 5MB. [503]";
			}
		}
		else
		{
			$res['error'] = "Upload failed. Please try again. [504]";
			$res['debug_files'] = $_FILES;
		}
	}
	else 
	{
		$res['error'] = "Upload failed. Please login. [401]";
		$res['redirect_to_login'] = true;
	}
	$app->response->headers->set('Content-Type', 'application/json');
	$body = json_encode($res);
	$app->response->setBody($body);
	$app->log->info('Processed Upload: ' . $body);
	
})->name('POST/api/upload');

/* *************************************************************************** */
/* Profile Editor                                                              */
/* *************************************************************************** */
$app->get('/manage/profile', $isAuth, function() use ($app) {
	// TODO Display profile editor
	$data = array(
		'current_page' => '/manage/profile',
	);
	$app->render("preferences.twig", $data);
})->name('GET/manage/profile');

$app->post('/manage/profile', $isAuth, function() use ($app) {
	// TODO Save profile edits
})->name('POST/manage/profile');

/* *************************************************************************** */
/* Preferences                                                                 */
/* *************************************************************************** */
$app->get('/manage/preferences', $isAuth, function() use ($app) {
	// TODO Display preferences editor
	$data = array(
		'current_page' => '/manage/preferences',
	);
	$app->render("preferences.twig", $data);
})->name('GET/manage/preferences');

$app->post('/manage/preferences', $isAuth, function() use ($app) {
	// TODO Save preferences 
	$app->redirect($app->urlFor('GET/manage/preferences'));
})->name('POST/manage/preferences');

/* *************************************************************************** */
/* Photo Rendering                                                             */
/* *************************************************************************** */
$app->get('/original/:id', function($id) use ($app) {
	
	$app->resource = '/img/:id';
	
	if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\.(\w{3,4})/', $id, $m))
	{
		$photo_id = $m[1];
		$photo_type = $m[2];
		$photo = Models\Photo::find($photo_id);
		if (isset($photo) && $photo->userCanSee($app->user) && strcasecmp($photo->type, $photo_type) === 0)
		{
			$base_path = str_replace("%BASEDIR%", BASEDIR, $app->config['photos_path']);
			$path = $photo->getOriginalPath($base_path);
			$app->response->headers->set('Content-type', Models\Photo::getMimeType($path));
			$app->response->setBody(file_get_contents($path));
		}
		else
		{
			$app->log->info('404 on /img/:id [Failed checks.]');
			$app->notFound();
		}
	}
	else
	{
		$app->log->info('404 on /img/:id [Bad URL.]');
		$app->notFound();
	}
})->name('/original/:id');

$app->get('/thumb/:id', function($id) use ($app) {
	
	$app->resource = '/thumb/:id';
	
	if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\.(\w{3,4})/', $id, $m))
	{
		$photo_id = $m[1];
		$photo_type = $m[2];
		$photo = Models\Photo::find($photo_id);
		if (isset($photo) && $photo->userCanSee($app->user) && strcasecmp($photo->type, $photo_type) === 0)
		{
			$base_path = str_replace("%BASEDIR%", BASEDIR, $app->config['photos_path']);
			$path = $photo->getThumbPath($base_path);
			$app->response->headers->set('Content-type', Models\Photo::getMimeType($path));
			$app->response->setBody(file_get_contents($path));
		}
		else
		{
			$app->log->info('404 on /img/:id [Failed checks.]');
			$app->notFound();
		}
	}
	else
	{
		$app->log->info('404 on /img/:id [Bad URL.]');
		$app->notFound();
	}
})->name('/thumb/:id');

$app->get('/img/:id', function($id) use ($app) {

	$app->resource = '/thumb/:id';

	if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\.(\w{3,4})/', $id, $m))
	{
		$photo_id = $m[1];
		$photo_type = $m[2];
		$photo = Models\Photo::find($photo_id);
		if (isset($photo) && $photo->userCanSee($app->user) && strcasecmp($photo->type, $photo_type) === 0)
		{
			$base_path = str_replace("%BASEDIR%", BASEDIR, $app->config['photos_path']);
			$path = $photo->getPath($base_path);
			$app->response->headers->set('Content-type', Models\Photo::getMimeType($path));
			$app->response->setBody(file_get_contents($path));
		}
		else
		{
			$app->log->info('404 on /img/:id [Failed checks.]');
			$app->notFound();
		}
	}
	else
	{
		$app->log->info('404 on /img/:id [Bad URL.]');
		$app->notFound();
	}
})->name('/img/:id');
	
$app->run();
