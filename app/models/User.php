<?php
namespace Schneenet\Photos\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
	use \Illuminate\Database\Eloquent\SoftDeletingTrait;
	
	protected $dates = ['created_at', 'modified_at', 'deleted_at'];
	
	public function albums()
	{
		return $this->hasMany("Schneenet\\Photos\\Models\\Album", 'owner_id');
	}
	
	public function photos()
	{
		return $this->hasMany("Schneenet\\Photos\\Models\\Photo", 'owner_id');
	}
	
	public function profile_values()
	{
		return $this->hasMany("Schneenet\\Photos\\Models\\ProfileValue");
	}
	
	public function profile_map($appUser = null)
	{
		$map = array();
		foreach ($this->profile_values as $profile_value)
		{
			if ($profile_value->userCanSee($appUser))
			{
				$nodes = explode('.', $profile_value->key);
				$current =& $map;
				foreach ($nodes as $node) {
					if (!isset($current[$node]))
					{
						$current[$node] = array();
					}
					$current =& $current[$node];
				}
				$current = $profile_value->value;
			}
		}
		return $map;
	}
	
	public function display_name()
	{
		if (isset($this->realname_first) && isset($this->realname_last))
		{
			return $this->realname_first . " " . $this->realname_last;
		}
		else if (isset($this->realname_first))
		{
			return $this->realname_first;
		}
		else if (isset($this->realname_last))
		{
			return $this->realname_last;
		}
		else 
		{
			return $this->username;
		}
	}
}