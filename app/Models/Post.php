<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'title',
        'content',
        'user_id'
    ];

    /**
     * User navigation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categories navigation (many-to-many)
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Verifies whether the logged user is authorized to manipulated the post
     */
    public function isAuthorized($user = null)
    {
        if (is_null($user) && !is_null(auth())) {
            $user = auth()->user();
        }
        if (!is_null($user))
        {
            return $user->id == $this->id || $user->hasPermissionTo('post-all');
        }
        return false;
    }

}
