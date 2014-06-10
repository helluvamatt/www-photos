<?php
namespace Schneenet\Photos\Models;

class ProfileValue extends Shareable
{
	use \Illuminate\Database\Eloquent\SoftDeletingTrait;
	
	protected $dates = ['created_at', 'modified_at', 'deleted_at'];
	
	protected $with = array('owner', 'sharedWith');
	
	public function owner()
	{
		return $this->belongsTo("Schneenet\\Photos\\Models\\User", 'user_id');
	}
	
	public function sharing()
	{
		$shared = $this->shared;
		return $shared == Shareable::SHARE_INHERIT ? $this->owner->default_sharing : $shared;
	}
	
	public function sharedWith()
	{
		return $this->morphToMany("Schneenet\\Photos\\Models\\User", 'shareable');
	}
}