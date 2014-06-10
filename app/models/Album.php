<?php
namespace Schneenet\Photos\Models;

class Album extends Shareable
{
	use \Illuminate\Database\Eloquent\SoftDeletingTrait;
	
	protected $with = array('owner');
	
	protected $dates = ['created_at', 'modified_at', 'deleted_at'];
	
	public function photos()
	{
		return $this->hasMany("Schneenet\\Photos\\Models\\Photo");
	}
	
	public function owner()
	{
		return $this->belongsTo("Schneenet\\Photos\\Models\\User", 'owner_id');
	}
	
	public function sharing()
	{
		$owner = $this->owner;
		return $this->shared == Shareable::SHARE_INHERIT ? $owner->default_sharing: $this->shared;
	}
	
	public function sharedWith()
	{
		return $this->morphToMany("Schneenet\\Photos\\Models\\User", 'shareable');
	}
	
}