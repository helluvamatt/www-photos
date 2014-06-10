<?php
namespace Schneenet\Photos\Models;

class Photo extends Shareable
{
	use \Illuminate\Database\Eloquent\SoftDeletingTrait;
	
	protected $with = array('owner');
	
	protected $dates = ['created_at', 'modified_at', 'deleted_at'];
	
	protected $visible = array('id', 'title', 'description', 'type', 'album_order', 'shared', 'created_at', 'updated_at', 'album_id', 'owner_id', 'deleted_at');
	
	public $incrementing = false;
	
	public function owner()
	{
		return $this->belongsTo("Schneenet\\Photos\\Models\\User", 'owner_id');
	}
	
	public function album()
	{
		return $this->belongsTo("Schneenet\\Photos\\Models\\Album");
	}
	
	public function sharing()
	{
		return $this->shared == Shareable::SHARE_INHERIT ? $this->album->sharing() : $this->shared;
	}
	
	public function sharedWith()
	{
		return $this->morphToMany("Schneenet\\Photos\\Models\\User", 'shareable');
	}
	
	public function getPath($basePath)
	{
		return $basePath . '/' . substr($this->id, 0, 1) . '/' . substr($this->id, 1, 1) . '/' . substr($this->id, 2, 1) . '/' . substr($this->id, 3, 1) . '/' . $this->id . "." . $this->type;  
	}
	
	public function getThumbPath($basePath)
	{
		return $basePath . '/' . substr($this->id, 0, 1) . '/' . substr($this->id, 1, 1) . '/' . substr($this->id, 2, 1) . '/' . substr($this->id, 3, 1) . '/' . $this->id . "_thumb." . $this->type;
	}
	
	public function getOriginalPath($basePath)
	{
		return $basePath . '/' . substr($this->id, 0, 1) . '/' . substr($this->id, 1, 1) . '/' . substr($this->id, 2, 1) . '/' . substr($this->id, 3, 1) . '/' . $this->id . "_original." . $this->type;
	}
	
	public static function createId()
	{
		// From: http://www.php.net/manual/en/function.uniqid.php#94959
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),
		
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,
		
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,
		
			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	
	public static function getMimeType($path)
	{
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		return $finfo->file($path);
	}
}
