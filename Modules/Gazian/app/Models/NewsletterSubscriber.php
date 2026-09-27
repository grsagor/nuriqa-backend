<?php

namespace Modules\Gazian\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $table = 'gazian_newsletter_subscribers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
    ];
}
