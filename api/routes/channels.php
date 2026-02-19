<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('location.{locationId}', function ($user, $locationId) {
    return $user->isAdmin() || (int) $user->location_id === (int) $locationId;
});
