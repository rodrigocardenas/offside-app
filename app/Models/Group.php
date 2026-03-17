<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Facades\CloudflareImages;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'created_by',
        'competition_id',
        'category',
        'reward_or_penalty',
        'expires_at',
        'cover_image',
        'cover_cloudflare_id',
        'cover_provider'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function groupRoles()
    {
        return $this->hasMany(GroupRole::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function templateQuestions()
    {
        return $this->hasMany(TemplateQuestion::class);
    }

    /**
     * Get the group's cover image URL.
     * Supports both Cloudflare Images and local storage.
     *
     * @param string $size Size preset: small, medium, large (default is medium)
     * @return string
     */
    public function getCoverImageUrl(string $size = 'medium'): string
    {
        // Try Cloudflare first if available and configured
        if ($this->cover_provider === 'cloudflare' && $this->cover_cloudflare_id) {
            try {
                $transformKey = "group_cover_{$size}";
                $url = CloudflareImages::getTransformedUrl(
                    $this->cover_cloudflare_id,
                    $transformKey
                );
                if ($url) {
                    return $url;
                }
            } catch (\Exception $e) {
                // Fallback to local if Cloudflare fails
            }
        }

        // Fallback to local storage
        if ($this->cover_image) {
            if (Storage::disk('public')->exists('groups/' . $this->cover_image)) {
                return asset('storage/groups/' . $this->cover_image);
            }

            $directPath = storage_path('app/public/groups/' . $this->cover_image);
            if (file_exists($directPath)) {
                return asset('storage/groups/' . $this->cover_image);
            }

            // Clean up if file doesn't exist
            $this->update(['cover_image' => null]);
        }

        // Default cover image
        return asset('images/group-default-cover.png');
    }

    /**
     * Get responsive srcset for cover image (Cloudflare only)
     *
     * @return string
     */
    public function getCoverImageSrcset(): string
    {
        if ($this->cover_provider === 'cloudflare' && $this->cover_cloudflare_id) {
            try {
                return CloudflareImages::getResponsiveSet(
                    $this->cover_cloudflare_id,
                    'group_cover'
                );
            } catch (\Exception $e) {
                return '';
            }
        }
        return '';
    }

    /**
     * Get the group's cover image URL via attribute accessor.
     * Kept for backward compatibility.
     *
     * @return string
     */
    public function getCoverImageAttribute()
    {
        // This is to handle both the new URL method and legacy attribute
        // If it's being accessed as an attribute, return the URL instead of the filename
        $value = $this->attributes['cover_image'] ?? null;
        if ($value && !str_starts_with($value, 'http')) {
            return $this->getCoverImageUrl('medium');
        }
        return $value;
    }

    /**
     * Get user's rank in the group
     *
     * @param int $userId
     * @return int|null
     */
    public function getUserRank($userId)
    {
        // Get all users with their points in this group
        $rankedUsers = $this->users()
            ->select('users.id')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->pluck('users.id')
            ->toArray();

        $rank = array_search($userId, $rankedUsers);

        return $rank !== false ? $rank + 1 : null;
    }

    /**
     * Check if user has pending predictions in this group
     *
     * @param int $userId
     * @return bool
     */
    public function hasPendingPredictions($userId)
    {
        return $this->questions()
            ->where('available_until', '>', now())
            ->whereDoesntHave('answers', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Get ranked users with points
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function rankedUsers()
    {
        return $this->users()
            ->select('users.*')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->get();
    }

    /**
     * Check if the group has expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the group is public
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->category === 'public';
    }

    /**
     * Scope: Get only active (non-expired) groups
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Get only public groups
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('category', 'public');
    }
}


    /**
     * Get user's rank in the group
     *
     * @param int $userId
     * @return int|null
     */
    public function getUserRank($userId)
    {
        // Get all users with their points in this group
        $rankedUsers = $this->users()
            ->select('users.id')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->pluck('users.id')
            ->toArray();

        $rank = array_search($userId, $rankedUsers);

        return $rank !== false ? $rank + 1 : null;
    }

    /**
     * Check if user has pending predictions in this group
     *
     * @param int $userId
     * @return bool
     */
    public function hasPendingPredictions($userId)
    {
        return $this->questions()
            ->where('available_until', '>', now())
            ->whereDoesntHave('answers', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Get ranked users with points
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function rankedUsers()
    {
        return $this->users()
            ->select('users.*')
            ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
            ->leftJoin('answers', 'users.id', '=', 'answers.user_id')
            ->leftJoin('questions', function($join) {
                $join->on('answers.question_id', '=', 'questions.id')
                     ->where('questions.group_id', '=', $this->id);
            })
            ->groupBy('users.id')
            ->orderBy('total_points', 'desc')
            ->get();
    }

    /**
     * Check if the group has expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the group is public
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->category === 'public';
    }

    /**
     * Scope: Get only active (non-expired) groups
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Get only public groups
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('category', 'public');
    }
}
