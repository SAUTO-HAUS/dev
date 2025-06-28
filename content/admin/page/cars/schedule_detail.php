<?php
global $scheduleType;
$schedule = [];

$groups = constant('\App\Helper\Schedules::' . $scheduleType);
foreach ($groups as $groupName => $groupSchedule) {
    foreach ($groupSchedule as $day => $times) {
        if (!isset($schedule[$day])) {
            $schedule[$day] = [];
        }
        $schedule[$day][$groupName] = $times;
    }
}

?>
<div class="schedule-container" style="font-size: 0.9em; color: #333; margin-top: 15px; text-align: left;">
    <h4 style="margin-bottom: 10px; text-align: center;"><?= __('schedule.schedule_publish')?></h4>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f5f5f5; border-bottom: 2px solid #ddd;">
                <th style="padding: 8px; text-align: left;"><?= __('schedule.day')?></th>
                <th style="padding: 8px; text-align: left;">Base</th>
                <th style="padding: 8px; text-align: left;">1</th>
                <th style="padding: 8px; text-align: left;">2</th>
                <th style="padding: 8px; text-align: left;">3</th>
                <th style="padding: 8px; text-align: left;">4</th>
                <th style="padding: 8px; text-align: left;">5</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($schedule as $day => $groupTimes) { ?>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= htmlspecialchars($day) ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['base']) ? htmlspecialchars(implode(', ', $groupTimes['base'])) : '-' ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['1']) ? htmlspecialchars(implode(', ', $groupTimes['1'])) : '-' ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['2']) ? htmlspecialchars(implode(', ', $groupTimes['2'])) : '-' ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['3']) ? htmlspecialchars(implode(', ', $groupTimes['3'])) : '-' ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['4']) ? htmlspecialchars(implode(', ', $groupTimes['4'])) : '-' ?>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?= isset($groupTimes['5']) ? htmlspecialchars(implode(', ', $groupTimes['5'])) : '-' ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
