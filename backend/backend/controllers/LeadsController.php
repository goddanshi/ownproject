<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Lead;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LeadsController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://91.218.245.170', 'http://91.218.245.170:8080', 'http://185.213.240.236'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionOptions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => true];
    }

    /**
     * Получить авторизованного пользователя из JWT токена
     */
    private function getAuthenticatedUser()
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return null;
        }

        return User::findOne(['id' => $payload['userId']]);
    }

    /**
     * Получить список всех лидов
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = Lead::find();

        // Менеджеры по продажам (role = 4) видят только свои лиды
        // Админы (role = 1) видят все лиды
        if ($user->role == 4) {
            $query->andWhere(['created_by' => $user->id]);
        }

        // Фильтр по менеджеру
        $managerId = Yii::$app->request->get('manager_id');
        if ($managerId) {
            $query->andWhere(['manager_id' => $managerId]);
        }

        // Фильтр по датам
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');
        if ($dateFrom) {
            $query->andWhere(['>=', 'date', $dateFrom]);
        }
        if ($dateTo) {
            $query->andWhere(['<=', 'date', $dateTo]);
        }

        $leads = $query->orderBy(['created_at' => SORT_DESC])->all();

        $result = [];
        foreach ($leads as $lead) {
            $result[] = $this->formatLead($lead);
        }

        return [
            'success' => true,
            'leads' => $result
        ];
    }

    /**
     * Получить один лид
     */
    public function actionView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $lead = Lead::findOne($id);
        if (!$lead) {
            return ['success' => false, 'message' => 'Лид не найден'];
        }

        // Менеджеры по продажам могут видеть только свои лиды
        if ($user->role == 4 && $lead->created_by != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }

        return [
            'success' => true,
            'lead' => $this->formatLead($lead)
        ];
    }

    /**
     * Создать новый лид
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = Yii::$app->request->post();

        $lead = new Lead();
        $lead->date = $data['date'] ?? time();
        $lead->website = $data['website'] ?? null;
        $lead->channel = $data['channel'] ?? null;
        $lead->contact_type = $data['contact_type'];
        $lead->contact_value = $data['contact_value'];
        $lead->audit_info = $data['audit_info'] ?? null;
        $lead->audit_status = $data['audit_status'] ?? Lead::NOT_READY;
        $lead->proposal_info = $data['proposal_info'] ?? null;
        $lead->proposal_status = $data['proposal_status'] ?? Lead::NOT_READY;
        $lead->price = $data['price'] ?? null;
        $lead->status = $data['status'] ?? Lead::STATUS_NEW;
        $lead->contact_date = $data['contact_date'] ?? null;
        $lead->comment = $data['comment'] ?? null;
        $lead->manager_id = $data['manager_id'] ?? null;
        $lead->created_by = $user->id;

        if ($lead->save()) {
            return [
                'success' => true,
                'message' => 'Лид успешно создан',
                'lead' => $this->formatLead($lead)
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка создания лида',
            'errors' => $lead->errors
        ];
    }

    /**
     * Обновить лид
     */
    public function actionUpdate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $lead = Lead::findOne($id);
        if (!$lead) {
            return ['success' => false, 'message' => 'Лид не найден'];
        }

        // Менеджеры по продажам могут редактировать только свои лиды
        if ($user->role == 4 && $lead->created_by != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }

        $data = Yii::$app->request->post();

        if (isset($data['date'])) $lead->date = $data['date'];
        if (isset($data['website'])) $lead->website = $data['website'];
        if (isset($data['channel'])) $lead->channel = $data['channel'];
        if (isset($data['contact_type'])) $lead->contact_type = $data['contact_type'];
        if (isset($data['contact_value'])) $lead->contact_value = $data['contact_value'];
        if (isset($data['audit_info'])) $lead->audit_info = $data['audit_info'];
        if (isset($data['audit_status'])) $lead->audit_status = $data['audit_status'];
        if (isset($data['proposal_info'])) $lead->proposal_info = $data['proposal_info'];
        if (isset($data['proposal_status'])) $lead->proposal_status = $data['proposal_status'];
        if (isset($data['price'])) $lead->price = $data['price'];
        if (isset($data['status'])) $lead->status = $data['status'];
        if (isset($data['contact_date'])) $lead->contact_date = $data['contact_date'];
        if (isset($data['comment'])) $lead->comment = $data['comment'];
        if (isset($data['manager_id'])) $lead->manager_id = $data['manager_id'];

        if ($lead->save()) {
            return [
                'success' => true,
                'message' => 'Лид успешно обновлен',
                'lead' => $this->formatLead($lead)
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка обновления лида',
            'errors' => $lead->errors
        ];
    }

    /**
     * Удалить лид
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $lead = Lead::findOne($id);
        if (!$lead) {
            return ['success' => false, 'message' => 'Лид не найден'];
        }

        // Менеджеры по продажам могут удалять только свои лиды
        if ($user->role == 4 && $lead->created_by != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }

        if ($lead->delete()) {
            return [
                'success' => true,
                'message' => 'Лид успешно удален'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления лида'
        ];
    }

    /**
     * Получить список менеджеров (для выбора в форме)
     */
    public function actionGetManagers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Получаем всех пользователей с ролью админ (1) и менеджер по продажам (4)
        $managers = User::find()
            ->where(['in', 'role', [User::ROLE_ADMIN, User::ROLE_SALES_MANAGER]])
            ->all();

        $result = [];
        foreach ($managers as $manager) {
            $result[] = [
                'id' => $manager->id,
                'username' => $manager->username,
                'name' => $manager->name,
                'surname' => $manager->surname,
                'role' => $manager->role,
            ];
        }

        return [
            'success' => true,
            'managers' => $result
        ];
    }

    /**
     * Получить список уникальных каналов (для автодополнения)
     */
    public function actionGetChannels()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $channels = Lead::find()
            ->select('channel')
            ->distinct()
            ->where(['not', ['channel' => null]])
            ->andWhere(['<>', 'channel', ''])
            ->orderBy('channel')
            ->column();

        return [
            'success' => true,
            'channels' => $channels
        ];
    }

    /**
     * Экспорт лидов в Excel
     */
    public function actionExport()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = Lead::find();

        // Менеджеры по продажам видят только свои лиды
        if ($user->role == 4) {
            $query->andWhere(['created_by' => $user->id]);
        }

        // Применяем фильтры
        $managerId = Yii::$app->request->get('manager_id');
        if ($managerId) {
            $query->andWhere(['manager_id' => $managerId]);
        }

        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');
        if ($dateFrom) {
            $query->andWhere(['>=', 'date', $dateFrom]);
        }
        if ($dateTo) {
            $query->andWhere(['<=', 'date', $dateTo]);
        }

        $leads = $query->orderBy(['date' => SORT_DESC])->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $headers = [
            'ID', 'Дата заявки', 'Сайт', 'Канал', 'Тип связи', 'Контакт',
            'Цена', 'Статус', 'Дата связи', 'Менеджер', 'Создал', 'Комментарий'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4A5568');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Данные
        $row = 2;
        foreach ($leads as $lead) {
            $sheet->setCellValue('A' . $row, $lead->id);
            $sheet->setCellValue('B' . $row, $lead->date ? date('Y-m-d', $lead->date) : '');
            $sheet->setCellValue('C' . $row, $lead->website);
            $sheet->setCellValue('D' . $row, $lead->channel);
            $sheet->setCellValue('E' . $row, $lead->getContactTypeLabel());
            $sheet->setCellValue('F' . $row, $lead->contact_value);
            $sheet->setCellValue('G' . $row, $lead->price);
            $sheet->setCellValue('H' . $row, $lead->getStatusLabel());
            $sheet->setCellValue('I' . $row, $lead->contact_date ? date('Y-m-d', $lead->contact_date) : '');
            $sheet->setCellValue('J' . $row, $lead->manager ? $lead->manager->name . ' ' . $lead->manager->surname : '');
            $sheet->setCellValue('K' . $row, $lead->creator->name . ' ' . $lead->creator->surname);
            $sheet->setCellValue('L' . $row, $lead->comment);
            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'leads_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Форматировать данные лида для отправки на frontend
     */
    private function formatLead($lead)
    {
        $result = [
            'id' => $lead->id,
            'date' => $lead->date,
            'website' => $lead->website,
            'channel' => $lead->channel,
            'contact_type' => $lead->contact_type,
            'contact_type_label' => $lead->getContactTypeLabel(),
            'contact_value' => $lead->contact_value,
            'audit_info' => $lead->audit_info,
            'audit_status' => $lead->audit_status,
            'audit_status_label' => $lead->getAuditStatusLabel(),
            'proposal_info' => $lead->proposal_info,
            'proposal_status' => $lead->proposal_status,
            'proposal_status_label' => $lead->getProposalStatusLabel(),
            'price' => $lead->price,
            'status' => $lead->status,
            'status_label' => $lead->getStatusLabel(),
            'contact_date' => $lead->contact_date,
            'contact_date_expired' => $lead->isContactDateExpired(),
            'contact_date_today' => $lead->isContactDateToday(),
            'comment' => $lead->comment,
            'creator' => [
                'id' => $lead->creator->id,
                'username' => $lead->creator->username,
                'name' => $lead->creator->name,
                'surname' => $lead->creator->surname,
            ],
            'manager_id' => $lead->manager_id,
            'manager' => null,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ];

        if ($lead->manager) {
            $result['manager'] = [
                'id' => $lead->manager->id,
                'username' => $lead->manager->username,
                'name' => $lead->manager->name,
                'surname' => $lead->manager->surname,
            ];
        }

        return $result;
    }
}
