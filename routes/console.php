<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();
