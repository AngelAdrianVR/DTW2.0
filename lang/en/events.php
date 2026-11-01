<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Messages for the public event registration form. Resolved with trans()
    | because the controller answers in the visitor's active locale (the
    | SetLocale middleware reads it from the session).
    |
    */

    'registered' => 'You are in! Your registration was saved. We will contact you if you win.',
    'closed' => 'Registration for this event is closed.',
    'phone_taken' => 'This phone number is already registered for this event.',
    'phone_invalid' => 'Enter a valid phone number with at least 10 digits.',

];
