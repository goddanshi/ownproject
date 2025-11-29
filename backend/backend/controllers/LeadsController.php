<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Lead;

class LeadsController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://185.104.113.132', 'http://185.104.113.132:8080', 'http://185.213.240.236'],
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

        $leads = Lead::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

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

        $data = Yii::$app->request->post();

        if (isset($data['date'])) $lead->date = $data['date'];
        if (isset($data['website'])) $lead->website = $data['website'];
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
     * Форматировать данные лида для отправки на frontend
     */
    private function formatLead($lead)
    {
        return [
            'id' => $lead->id,
            'date' => $lead->date,
            'website' => $lead->website,
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
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ];
    }
}
