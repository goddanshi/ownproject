<?php

namespace backend\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\Task;
use common\models\TimeTracking;
use common\models\User;
use common\models\Project;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StatisticsController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Статистика по задачам
     * GET /api/statistics/tasks
     * Параметры: project_id, user_id, date_from, date_to
     */
    public function actionTasks()
    {
        $projectId = Yii::$app->request->get('project_id');
        $userId = Yii::$app->request->get('user_id');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        $query = Task::find()->with(['project', 'assignees', 'timeTrackings']);

        // Фильтрация по проекту
        if ($projectId) {
            $query->andWhere(['project_id' => $projectId]);
        }

        // Фильтрация по пользователю (через назначения)
        if ($userId) {
            $query->joinWith('taskAssignments')
                ->andWhere(['task_assignment.user_id' => $userId]);
        }

        // Фильтрация по датам (по дате создания задачи)
        if ($dateFrom) {
            $query->andWhere(['>=', 'created_at', strtotime($dateFrom)]);
        }
        if ($dateTo) {
            $query->andWhere(['<=', 'created_at', strtotime($dateTo . ' 23:59:59')]);
        }

        $tasks = $query->all();

        $result = [];
        foreach ($tasks as $task) {
            // Подсчет общего времени выполнения
            $totalTime = $task->getTotalTime();

            // Подготовка данных об исполнителях
            $assignees = [];
            foreach ($task->assignees as $assignee) {
                $assignees[] = [
                    'id' => $assignee->id,
                    'name' => $assignee->name . ' ' . $assignee->surname,
                    'time' => $task->getUserTime($assignee->id),
                ];
            }

            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'project' => $task->project ? $task->project->name : null,
                'project_id' => $task->project_id,
                'status' => $task->getStatusLabel(),
                'priority' => $task->getPriorityLabel(),
                'estimated_time' => $task->estimated_time ?? 0,
                'total_time' => $totalTime,
                'time_diff' => $totalTime - ($task->estimated_time ?? 0),
                'time_diff_percent' => $task->estimated_time > 0
                    ? round((($totalTime - $task->estimated_time) / $task->estimated_time) * 100, 2)
                    : 0,
                'assignees' => $assignees,
                'created_at' => $task->created_at,
                'deadline' => $task->deadline,
            ];
        }

        return [
            'success' => true,
            'tasks' => $result,
            'total' => count($result),
        ];
    }

    /**
     * Статистика по проектам
     * GET /api/statistics/projects
     * Параметры: date_from, date_to
     */
    public function actionProjects()
    {
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        $projects = Project::find()->with(['tasks.timeTrackings'])->all();

        $result = [];
        foreach ($projects as $project) {
            $totalTime = 0;
            $totalEstimatedTime = 0;
            $taskCount = 0;
            $completedTaskCount = 0;

            foreach ($project->tasks as $task) {
                // Фильтрация по датам (учитываем время трекинга)
                $taskTime = 0;
                foreach ($task->timeTrackings as $tracking) {
                    $trackingDate = $tracking->started_at;

                    if ($dateFrom && $trackingDate < strtotime($dateFrom)) {
                        continue;
                    }
                    if ($dateTo && $trackingDate > strtotime($dateTo . ' 23:59:59')) {
                        continue;
                    }

                    $taskTime += $tracking->duration ?? 0;
                }

                // Если есть фильтр по датам и нет подходящих трекингов, пропускаем задачу
                if (($dateFrom || $dateTo) && $taskTime === 0) {
                    continue;
                }

                $totalTime += $taskTime;
                $totalEstimatedTime += $task->estimated_time ?? 0;
                $taskCount++;

                if ($task->status == Task::STATUS_DONE) {
                    $completedTaskCount++;
                }
            }

            if ($taskCount > 0 || !$dateFrom) {
                $result[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'task_count' => $taskCount,
                    'completed_tasks' => $completedTaskCount,
                    'total_time' => $totalTime,
                    'estimated_time' => $totalEstimatedTime,
                    'time_diff' => $totalTime - $totalEstimatedTime,
                    'completion_rate' => $taskCount > 0
                        ? round(($completedTaskCount / $taskCount) * 100, 2)
                        : 0,
                ];
            }
        }

        return [
            'success' => true,
            'projects' => $result,
            'total' => count($result),
        ];
    }

    /**
     * Статистика по сотрудникам
     * GET /api/statistics/employees
     * Параметры: user_id, project_id, date_from, date_to
     */
    public function actionEmployees()
    {
        $userId = Yii::$app->request->get('user_id');
        $projectId = Yii::$app->request->get('project_id');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        $query = User::find();

        if ($userId) {
            $query->andWhere(['id' => $userId]);
        }

        $users = $query->all();

        $result = [];
        foreach ($users as $user) {
            $trackingQuery = TimeTracking::find()
                ->joinWith('task.project')
                ->where(['time_tracking.user_id' => $user->id]);

            // Фильтрация по проекту
            if ($projectId) {
                $trackingQuery->andWhere(['tasks.project_id' => $projectId]);
            }

            // Фильтрация по датам
            if ($dateFrom) {
                $trackingQuery->andWhere(['>=', 'time_tracking.started_at', strtotime($dateFrom)]);
            }
            if ($dateTo) {
                $trackingQuery->andWhere(['<=', 'time_tracking.started_at', strtotime($dateTo . ' 23:59:59')]);
            }

            $trackings = $trackingQuery->all();

            $totalTime = 0;
            $taskDetails = [];
            $projectBreakdown = [];

            foreach ($trackings as $tracking) {
                $duration = $tracking->duration ?? 0;
                $totalTime += $duration;

                $task = $tracking->task;
                $project = $task ? $task->project : null;

                // Детализация по задачам
                $taskKey = $task ? $task->id : 0;
                if (!isset($taskDetails[$taskKey])) {
                    $taskDetails[$taskKey] = [
                        'task_id' => $task ? $task->id : null,
                        'task_title' => $task ? $task->title : 'Неизвестная задача',
                        'project_name' => $project ? $project->name : 'Без проекта',
                        'project_id' => $project ? $project->id : null,
                        'time' => 0,
                    ];
                }
                $taskDetails[$taskKey]['time'] += $duration;

                // Детализация по проектам
                $projectKey = $project ? $project->id : 0;
                if (!isset($projectBreakdown[$projectKey])) {
                    $projectBreakdown[$projectKey] = [
                        'project_id' => $project ? $project->id : null,
                        'project_name' => $project ? $project->name : 'Без проекта',
                        'time' => 0,
                    ];
                }
                $projectBreakdown[$projectKey]['time'] += $duration;
            }

            // Пропускаем пользователей без трекингов при наличии фильтров
            if ($totalTime === 0 && ($dateFrom || $dateTo || $projectId)) {
                continue;
            }

            $result[] = [
                'id' => $user->id,
                'name' => $user->name . ' ' . $user->surname,
                'username' => $user->username,
                'total_time' => $totalTime,
                'projects' => array_values($projectBreakdown),
                'tasks' => array_values($taskDetails),
            ];
        }

        return [
            'success' => true,
            'employees' => $result,
            'total' => count($result),
        ];
    }

    /**
     * Отчет о превышениях времени
     * GET /api/statistics/overruns
     * Параметры: project_id, user_id, date_from, date_to
     */
    public function actionOverruns()
    {
        $projectId = Yii::$app->request->get('project_id');
        $userId = Yii::$app->request->get('user_id');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        $query = Task::find()
            ->with(['project', 'assignees', 'timeTrackings'])
            ->where(['>', 'estimated_time', 0]); // Только задачи с указанным плановым временем

        // Фильтрация по проекту
        if ($projectId) {
            $query->andWhere(['project_id' => $projectId]);
        }

        // Фильтрация по пользователю
        if ($userId) {
            $query->joinWith('taskAssignments')
                ->andWhere(['task_assignment.user_id' => $userId]);
        }

        // Фильтрация по датам
        if ($dateFrom) {
            $query->andWhere(['>=', 'created_at', strtotime($dateFrom)]);
        }
        if ($dateTo) {
            $query->andWhere(['<=', 'created_at', strtotime($dateTo . ' 23:59:59')]);
        }

        $tasks = $query->all();

        $result = [];
        foreach ($tasks as $task) {
            $totalTime = $task->getTotalTime();
            $estimatedTime = $task->estimated_time;

            // Учитываем только превышения
            if ($totalTime > $estimatedTime) {
                $overrun = $totalTime - $estimatedTime;
                $overrunPercent = round(($overrun / $estimatedTime) * 100, 2);

                // Данные по исполнителям
                $assignees = [];
                foreach ($task->assignees as $assignee) {
                    $userTime = $task->getUserTime($assignee->id);
                    if ($userTime > 0) {
                        $assignees[] = [
                            'id' => $assignee->id,
                            'name' => $assignee->name . ' ' . $assignee->surname,
                            'time' => $userTime,
                            'share_percent' => $totalTime > 0
                                ? round(($userTime / $totalTime) * 100, 2)
                                : 0,
                        ];
                    }
                }

                $result[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $task->project ? $task->project->name : null,
                    'project_id' => $task->project_id,
                    'estimated_time' => $estimatedTime,
                    'total_time' => $totalTime,
                    'overrun' => $overrun,
                    'overrun_percent' => $overrunPercent,
                    'status' => $task->getStatusLabel(),
                    'assignees' => $assignees,
                    'created_at' => $task->created_at,
                    'deadline' => $task->deadline,
                ];
            }
        }

        // Сортировка по проценту превышения (от большего к меньшему)
        usort($result, function($a, $b) {
            return $b['overrun_percent'] <=> $a['overrun_percent'];
        });

        return [
            'success' => true,
            'overruns' => $result,
            'total' => count($result),
        ];
    }

    /**
     * Экспорт статистики по задачам в Excel
     * GET /api/statistics/export-tasks
     */
    public function actionExportTasks()
    {
        $data = $this->actionTasks();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовок
        $sheet->setCellValue('A1', 'Статистика по задачам');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Заголовки таблицы
        $headers = ['Задача', 'Проект', 'Статус', 'Приоритет', 'План (ч)', 'Факт (ч)', 'Разница (ч)', '%', 'Исполнители'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Данные
        $row = 4;
        foreach ($data['tasks'] as $task) {
            $sheet->setCellValue('A' . $row, $task['title']);
            $sheet->setCellValue('B' . $row, $task['project'] ?? '—');
            $sheet->setCellValue('C' . $row, $task['status']);
            $sheet->setCellValue('D' . $row, $task['priority']);
            $sheet->setCellValue('E' . $row, round($task['estimated_time'] / 3600, 2));
            $sheet->setCellValue('F' . $row, round($task['total_time'] / 3600, 2));
            $sheet->setCellValue('G' . $row, round($task['time_diff'] / 3600, 2));
            $sheet->setCellValue('H' . $row, ($task['time_diff_percent'] > 0 ? '+' : '') . $task['time_diff_percent'] . '%');

            $assignees = array_map(function($a) {
                return $a['name'] . ' (' . round($a['time'] / 3600, 2) . 'ч)';
            }, $task['assignees']);
            $sheet->setCellValue('I' . $row, implode(', ', $assignees));

            $row++;
        }

        // Автоширина столбцов
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Отправка файла
        $filename = 'statistics_tasks_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Экспорт статистики по проектам в Excel
     * GET /api/statistics/export-projects
     */
    public function actionExportProjects()
    {
        $data = $this->actionProjects();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовок
        $sheet->setCellValue('A1', 'Статистика по проектам');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Заголовки таблицы
        $headers = ['Проект', 'Всего задач', 'Завершено', '% выполнения', 'План (ч)', 'Факт (ч)', 'Разница (ч)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Данные
        $row = 4;
        foreach ($data['projects'] as $project) {
            $sheet->setCellValue('A' . $row, $project['name']);
            $sheet->setCellValue('B' . $row, $project['task_count']);
            $sheet->setCellValue('C' . $row, $project['completed_tasks']);
            $sheet->setCellValue('D' . $row, $project['completion_rate'] . '%');
            $sheet->setCellValue('E' . $row, round($project['estimated_time'] / 3600, 2));
            $sheet->setCellValue('F' . $row, round($project['total_time'] / 3600, 2));
            $sheet->setCellValue('G' . $row, round($project['time_diff'] / 3600, 2));
            $row++;
        }

        // Автоширина столбцов
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Отправка файла
        $filename = 'statistics_projects_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Экспорт статистики по сотрудникам в Excel
     * GET /api/statistics/export-employees
     */
    public function actionExportEmployees()
    {
        $data = $this->actionEmployees();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовок
        $sheet->setCellValue('A1', 'Статистика по сотрудникам');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;
        foreach ($data['employees'] as $employee) {
            // Заголовок сотрудника
            $sheet->setCellValue('A' . $row, $employee['name']);
            $sheet->setCellValue('C' . $row, 'Всего: ' . round($employee['total_time'] / 3600, 2) . ' ч');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFDDEEFF');
            $row++;

            // По проектам
            if (!empty($employee['projects'])) {
                $sheet->setCellValue('A' . $row, 'Проект');
                $sheet->setCellValue('B' . $row, 'Время (ч)');
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
                $row++;

                foreach ($employee['projects'] as $project) {
                    $sheet->setCellValue('A' . $row, $project['project_name']);
                    $sheet->setCellValue('B' . $row, round($project['time'] / 3600, 2));
                    $row++;
                }
                $row++;
            }

            // По задачам
            if (!empty($employee['tasks'])) {
                $sheet->setCellValue('A' . $row, 'Задача');
                $sheet->setCellValue('B' . $row, 'Проект');
                $sheet->setCellValue('C' . $row, 'Время (ч)');
                $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
                $row++;

                foreach ($employee['tasks'] as $task) {
                    $sheet->setCellValue('A' . $row, $task['task_title']);
                    $sheet->setCellValue('B' . $row, $task['project_name']);
                    $sheet->setCellValue('C' . $row, round($task['time'] / 3600, 2));
                    $row++;
                }
            }

            $row += 2; // Пропуск между сотрудниками
        }

        // Автоширина столбцов
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Отправка файла
        $filename = 'statistics_employees_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Экспорт отчета о превышениях в Excel
     * GET /api/statistics/export-overruns
     */
    public function actionExportOverruns()
    {
        $data = $this->actionOverruns();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовок
        $sheet->setCellValue('A1', 'Отчет о превышениях времени');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Заголовки таблицы
        $headers = ['Задача', 'Проект', 'Статус', 'План (ч)', 'Факт (ч)', 'Превышение (ч)', '% превышения', 'Исполнители'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Данные
        $row = 4;
        foreach ($data['overruns'] as $task) {
            $sheet->setCellValue('A' . $row, $task['title']);
            $sheet->setCellValue('B' . $row, $task['project'] ?? '—');
            $sheet->setCellValue('C' . $row, $task['status']);
            $sheet->setCellValue('D' . $row, round($task['estimated_time'] / 3600, 2));
            $sheet->setCellValue('E' . $row, round($task['total_time'] / 3600, 2));
            $sheet->setCellValue('F' . $row, round($task['overrun'] / 3600, 2));
            $sheet->setCellValue('G' . $row, '+' . $task['overrun_percent'] . '%');

            $assignees = array_map(function($a) {
                return $a['name'] . ' (' . round($a['time'] / 3600, 2) . 'ч, ' . $a['share_percent'] . '%)';
            }, $task['assignees']);
            $sheet->setCellValue('H' . $row, implode(', ', $assignees));

            // Подсветка превышений красным
            $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->getColor()->setARGB('FFFF0000');

            $row++;
        }

        // Автоширина столбцов
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Отправка файла
        $filename = 'statistics_overruns_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
