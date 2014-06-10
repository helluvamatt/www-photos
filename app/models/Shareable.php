<?php
namespace Schneenet\Photos\Models;

abstract class Shareable extends \Illuminate\Database\Eloquent\Model
{
	const SHARE_PRIVATE = 0;
	const SHARE_INHERIT = 1;
	const SHARE_CUSTOM = 2;
	const SHARE_PUBLIC = 3;
	
	public abstract function owner();
	public abstract function sharing();
	public abstract function sharedWith();
	
	public function userCanSee(User $user = null)
	{
		// automatically calls to parents on inherit
		$sharing = $this->sharing();
	
		if ($sharing == Shareable::SHARE_PUBLIC)
		{
			// anyone can see PUBLIC, even non-logged in
			return true;
		}
		if (isset($user))
		{
			if ($this->owner->id === $user->id)
			{
				// owner can always see their own stuff
				return true;
			}
			else if ($sharing == Shareable::SHARE_CUSTOM)
			{
				// handle custom sharing
				return $this->sharedWith->contains($user->id);
			}
		}
	
		// in all other cases, hide the object
		return false;
	}
}