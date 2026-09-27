<?php

use Illuminate\Support\Facades\Schedule;

// Prune expired login tokens daily
Schedule::command('model:prune', ['--model' => \App\Models\LoginToken::class])->daily();
