<?php

return [
    // StartPomodoroUseCase
    'authorize_first' => 'Please authorize in the bot first using the /start command',
    'active_session_exists' => 'You already have an active session',
    'add_settings_first' => 'Please set up Pomodoro settings first using the /addpomosettings command',

    // AddPomodoroSettingsForUserUseCase
    'enter_work_duration' => 'Please enter the work duration (one pomodoro)',
    'try_later' => 'Please try again later',

    // GetPomodoroSettingsUseCase
    'user_not_found' => 'User not found. Please register first using the /start command',
    'no_settings' => 'You don\'t have any Pomodoro settings yet. Use the /addpomosettings command to set them up.',
    'settings_header' => 'Your current Pomodoro settings:',
    'work_time' => '⏱️ Work time: :duration min',
    'break_time' => '⏸️ Break time: :duration min',
    'repeats_count' => '🔄 Number of repeats: :count',
    'long_break_duration' => '⏸️ Long break: :duration min',
    'cycles_before_long_break' => '🔄 Cycles before long break: :count',
    'not_set' => 'not set',
    'start_timer_hint' => 'To start the timer, use the /startpomodoro command',
    'error_getting_data' => 'An error occurred while retrieving your data. Please try again later.',

    // GetTodaySessionsUseCase
    'no_sessions_today' => 'You have no sessions for today.',
    'sessions_header' => 'Sessions for today:',
    'table_header_num' => '№',
    'table_header_start_time' => 'Start time',
    'table_header_status' => 'Status',
    'table_header_cycle' => 'Cycle',
    'table_header_end_time' => 'End time',

    // Status texts
    'status_paused' => 'Paused',
    'status_finished' => 'Finished',
    'status_work' => 'Work',
    'status_break' => 'Break',
    'status_long_break' => 'Long break',

    // AddWorkDurationUseCase
    'work_duration_saved' => 'Work duration saved successfully. Now enter the break duration',
    'work_duration_updated' => 'You already had Pomodoro settings. Work duration updated. Now enter the break duration',

    // AddBreakDurationUseCase
    'break_duration_saved' => 'Break duration saved successfully. Now enter the number of repeats',

    // AddRepeatsCountUseCase
    'repeats_count_saved' => 'Number of repeats saved successfully. Now enter the long break duration',

    // AddLongBreakDurationUseCase
    'long_break_duration_saved' => 'Long break duration saved successfully. Now enter the number of cycles before long break',

    // AddCyclesBeforeLongBreakUseCase
    'cycles_exceed_repeats' => 'Number of cycles before long break must be less than total repeats. Please enter the number of cycles again',
    'cycles_saved' => 'Number of cycles before long break saved successfully. Setup complete! Now you can use the /startpomodoro command to start working',

    // ProcessPomodoroStageJob
    'setup_pomodoro_first' => 'Please set up Pomodoro settings first using the /addpomosettings command',
    'work_started' => 'Time to work! Work for :duration minutes.',
    'long_break_started' => 'Long break after :cycle cycles. Rest for :duration minutes.',
    'short_break_started' => 'Short break. Rest for :duration minutes.',
    'pomodoro_completed' => 'Congratulations! You have completed all Pomodoro cycles.',
];
