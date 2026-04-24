<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\DTOs\GetTodaySessionsDTO;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class GetTodaySessionsUseCase
{
    public function __construct(
        private UserAdapterInterface $userAdapter,
        private TelegramAdapterInterface $telegramAdapter,
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository
    ) {}

    public function execute(GetTodaySessionsDTO $data): void
    {
        try {
            $user = $this->userAdapter->getUserByTelegramId($data->telegramId);
        } catch (ModelNotFoundException $e) {
            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('pomodoro.authorize_first')
            );

            return;
        }

        $todaySessions = $this->pomodoroSessionsRepository->getTodaySessions($user->id);

        if ($todaySessions->isEmpty()) {
            $this->telegramAdapter->sendMessage(
                $data->telegramId,
                __('pomodoro.no_sessions_today')
            );

            return;
        }

        $table = '<b>'.__('pomodoro.sessions_header')."</b>\n\n";
        $table .= '<pre>';
        $table .= "+---+---------------+---------------+------+----------------+\n";
        $table .= '| '.__('pomodoro.table_header_num').' | '.__('pomodoro.table_header_start_time').'  | '.__('pomodoro.table_header_status').'       | '.__('pomodoro.table_header_cycle').'  | '.__('pomodoro.table_header_end_time')."|\n";
        $table .= "+---+---------------+---------------+------+----------------+\n";

        foreach ($todaySessions as $index => $session) {
            /** @var PomodoroSession $session */
            if (is_null($session->start_at)) {
                continue;
            }
            $startTime = $session->start_at->format('H:i:s');

            $status = $this->getStatusText($session->current_status->value);
            $cycle = $session->current_cycle;
            $endTime = $session->end_at ? $session->end_at->format('H:i:s') : '-';

            $num = str_pad((string) ($index + 1), 2, ' ', STR_PAD_BOTH);
            $startTimePad = str_pad($startTime, 14, ' ', STR_PAD_RIGHT);
            $statusPad = str_pad(mb_substr($status, 0, 14), 14, ' ', STR_PAD_RIGHT);
            $cyclePad = str_pad((string) $cycle, 4, ' ', STR_PAD_BOTH);
            $endTimePad = str_pad($endTime, 15, ' ', STR_PAD_RIGHT);

            $table .= '| '.$num.' | '.$startTimePad.' | '.$statusPad.' | '.$cyclePad.' | '.$endTimePad." |\n";
        }

        $table .= "+---+---------------+---------------+------+----------------+\n";
        $table .= '</pre>';

        $this->telegramAdapter->sendMessage(
            chatId: $data->telegramId,
            text: $table
        );
    }

    private function getStatusText(string $status): string
    {
        $statusMap = [
            'paused' => __('pomodoro.status_paused'),
            'finished' => __('pomodoro.status_finished'),
            'work' => __('pomodoro.status_work'),
            'break' => __('pomodoro.status_break'),
            'long_break' => __('pomodoro.status_long_break'),
        ];

        return $statusMap[$status] ?? $status;
    }
}
