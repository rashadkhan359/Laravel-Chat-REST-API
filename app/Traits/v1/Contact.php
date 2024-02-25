<?php

namespace App\Traits\v1;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

trait Contact
{

    /**
     * Get user with avatar (formatted).
     *
     * @param User $user
     * @return Model
     */
    public function getUserWithAvatar(User $user)
    {
        if ($user->avatar == 'avatar.png' && config('messenger.gravatar.enabled')) {
            $imageSize = config('messenger.gravatar.image_size');
            $imageset = config('messenger.gravatar.imageset');
            $user->avatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?s=' . $imageSize . '&d=' . $imageset;
        } else {
            $user->avatar = $this->getUserAvatarUrl($user->avatar);
        }
        return $user;
    }
    /**
     * Return a storage instance with disk name specified in the config.
     */
    public function storage()
    {
        return Storage::disk(config('messenger.storage_disk_name'));
    }

    /**
     * Get user avatar url.
     *
     * @param string $user_avatar_name
     * @return string
     */
    public function getUserAvatarUrl($user_avatar_name)
    {
        return $this->storage()->url(config('messenger.user_avatar.folder') . '/' . $user_avatar_name);
    }

    /**
     * Get attachment's url.
     *
     * @param string $attachment_name
     * @return string
     */
    public function getAttachmentUrl($attachment_name)
    {
        return $this->storage()->url(config('messenger.attachments.folder') . '/' . $attachment_name);
    }

        /**
     * This method returns the allowed image extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedImages()
    {
        return config('messenger.attachments.allowed_images');
    }
    
    /**
     * This method returns the allowed file extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedFiles()
    {
        return config('messenger.attachments.allowed_files');
    }
}
