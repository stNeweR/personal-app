<?php

return [
    // StartPomodoroUseCase
    'authorize_first' => 'Сначала авторизуйтесь в боте командой /start',
    'active_session_exists' => 'У вас уже есть активная сессия',
    'add_settings_first' => 'Добавьте сначала настройки помодоро командой - /addpomosettings',

    // AddPomodoroSettingsForUserUseCase
    'enter_work_duration' => 'Пожалуйста теперь введите длительность рабочего времени (одного помодоро)',
    'try_later' => 'Попробуйте позже',

    // GetPomodoroSettingsUseCase
    'user_not_found' => 'Пользователь не найден. Пожалуйста, сначала зарегистрируйтесь, используя команду /start',
    'no_settings' => 'У вас пока нет настроек Pomodoro. Используйте команду /addpomosettings для их настройки.',
    'settings_header' => 'Ваши текущие настройки Pomodoro:',
    'work_time' => '⏱️ Рабочее время: :duration мин',
    'break_time' => '⏸️ Время перерыва: :duration мин',
    'repeats_count' => '🔄 Количество повторений: :count',
    'long_break_duration' => '⏸️ Длительный перерыв: :duration мин',
    'cycles_before_long_break' => '🔄 Циклов перед длинным перерывом: :count',
    'not_set' => 'не установлено',
    'start_timer_hint' => 'Чтобы запустить таймер вызовите команду /startpomodoro',
    'error_getting_data' => 'Произошла ошибка при получении ваших данных. Попробуйте позже.',

    // GetTodaySessionsUseCase
    'no_sessions_today' => 'У вас нет сессий за сегодняшний день.',
    'sessions_header' => 'Сессии за сегодня:',
    'table_header_num' => '№',
    'table_header_start_time' => 'Время начала',
    'table_header_status' => 'Статус',
    'table_header_cycle' => 'Цикл',
    'table_header_end_time' => 'Время окончания',

    // Status texts
    'status_paused' => 'Пауза',
    'status_finished' => 'Завершено',
    'status_work' => 'Работа',
    'status_break' => 'Перерыв',
    'status_long_break' => 'Длинный перерыв',

    // AddWorkDurationUseCase
    'work_duration_saved' => 'Успешно сохранили рабочее время. Теперь введите время перерыва',
    'work_duration_updated' => 'У вас уже была настройка для помодоро таймера. Рабочее время обновлено. Теперь введите время перерыва',

    // AddBreakDurationUseCase
    'break_duration_saved' => 'Успешно сохранили время перерыва. Теперь введите количество повторов',

    // AddRepeatsCountUseCase
    'repeats_count_saved' => 'Успешно сохранили количество повторов. Теперь введите длительность длинного перерыва',

    // AddLongBreakDurationUseCase
    'long_break_duration_saved' => 'Успешно сохранили длительность длинного перерыва. Теперь введите количество циклов до длинного перерыва',

    // AddCyclesBeforeLongBreakUseCase
    'cycles_exceed_repeats' => 'Количество повторов до длинного перерыва должно быть меньше общего количества повторов. Введите ещё раз количество повторов до длинного перерыва',
    'cycles_saved' => 'Успешно сохранили количество циклов до длинного перерыва. Настройка завершена! Теперь можете вызвать команду /startpomodoro чтобы начать работать',

    // ProcessPomodoroStageJob
    'setup_pomodoro_first' => 'Сначала установите настройки Pomodoro с помощью команды /addpomosettings',
    'work_started' => 'Пора работать! Работайте в течение :duration минут.',
    'long_break_started' => 'Длинный перерыв после :cycle цикла. Отдохните :duration минут.',
    'short_break_started' => 'Короткий перерыв. Отдохните :duration минут.',
    'pomodoro_completed' => 'Поздравляем! Вы завершили все циклы Pomodoro.',
];
