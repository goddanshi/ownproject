<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Team;
use common\models\TeamMember;

class TeamsController extends Controller
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

    // Получить список команд
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $teams = Team::getAccessibleTeams($user);

        $result = [];
        foreach ($teams as $team) {
            $result[] = [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'teamlead' => [
                    'id' => $team->teamlead->id,
                    'username' => $team->teamlead->username,
                    'email' => $team->teamlead->email,
                ],
                'members_count' => count($team->members),
                'created_at' => $team->created_at,
            ];
        }

        return [
            'success' => true,
            'teams' => $result
        ];
    }

    // Получить детальную информацию о команде
    public function actionView()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $team = Team::findOne($id);

        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }

        // Проверка прав доступа
        if (!$this->canAccessTeam($user, $team)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $members = [];
        foreach ($team->members as $member) {
            $members[] = [
                'id' => $member->id,
                'username' => $member->username,
                'email' => $member->email,
                'name' => $member->name,
                'surname' => $member->surname,
            ];
        }

        return [
            'success' => true,
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'teamlead' => [
                    'id' => $team->teamlead->id,
                    'username' => $team->teamlead->username,
                    'email' => $team->teamlead->email,
                    'name' => $team->teamlead->name,
                    'surname' => $team->teamlead->surname,
                ],
                'members' => $members,
                'created_at' => $team->created_at,
                'updated_at' => $team->updated_at,
            ]
        ];
    }

    // Создать команду
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Админ и тимлиды могут создавать команды
        if ($user->role != User::ROLE_ADMIN && $user->role != User::ROLE_TEAMLEAD) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $team = new Team();
        $team->name = $data['name'] ?? '';
        $team->description = $data['description'] ?? '';
        $team->teamlead_id = $data['teamlead_id'] ?? null;

        if (!$team->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка создания команды',
                'errors' => $team->errors
            ];
        }

        // Добавляем участников если указаны
        if (!empty($data['member_ids'])) {
            foreach ($data['member_ids'] as $memberId) {
                $team->addMember($memberId);
            }
        }

        return [
            'success' => true,
            'message' => 'Команда успешно создана',
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ]
        ];
    }

    // Обновить команду
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = $data['id'] ?? null;

        $team = Team::findOne($id);
        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }

        // Проверка прав: админ или тимлид этой команды
        if (!$this->canManageTeam($user, $team)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $team->name = $data['name'] ?? $team->name;
        $team->description = $data['description'] ?? $team->description;

        // Только админ может менять тимлида
        if ($user->role == User::ROLE_ADMIN && isset($data['teamlead_id'])) {
            $team->teamlead_id = $data['teamlead_id'];
        }

        if (!$team->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления команды',
                'errors' => $team->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Команда успешно обновлена'
        ];
    }

    // Удалить команду
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $team = Team::findOne($id);

        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }

        // Только админ может удалять команды
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        if ($team->delete()) {
            return [
                'success' => true,
                'message' => 'Команда успешно удалена'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления команды'
        ];
    }

    // Добавить участника в команду
    public function actionAddMember()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $teamId = $data['team_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        $team = Team::findOne($teamId);
        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }

        // Проверка прав
        if (!$this->canManageTeam($user, $team)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        if ($team->addMember($userId)) {
            return [
                'success' => true,
                'message' => 'Участник добавлен в команду'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка добавления участника'
        ];
    }

    // Удалить участника из команды
    public function actionRemoveMember()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $teamId = $data['team_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        $team = Team::findOne($teamId);
        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }

        // Проверка прав
        if (!$this->canManageTeam($user, $team)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        if ($team->removeMember($userId)) {
            return [
                'success' => true,
                'message' => 'Участник удален из команды'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления участника'
        ];
    }

    // Получить список доступных тимлидов
    public function actionGetTeamleads()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user || $user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $teamleads = User::find()
            ->where(['role' => User::ROLE_TEAMLEAD])
            ->all();

        $result = [];
        foreach ($teamleads as $teamlead) {
            $result[] = [
                'id' => $teamlead->id,
                'username' => $teamlead->username,
                'email' => $teamlead->email,
                'name' => $teamlead->name,
                'surname' => $teamlead->surname,
            ];
        }

        return [
            'success' => true,
            'teamleads' => $result
        ];
    }

    // Получить список доступных сотрудников
    public function actionGetEmployees()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $employees = User::find()
            ->where(['role' => User::ROLE_EMPLOYER])
            ->all();

        $result = [];
        foreach ($employees as $employee) {
            $result[] = [
                'id' => $employee->id,
                'username' => $employee->username,
                'email' => $employee->email,
                'name' => $employee->name,
                'surname' => $employee->surname,
            ];
        }

        return [
            'success' => true,
            'employees' => $result
        ];
    }

    // === Вспомогательные методы ===

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

    // Может ли пользователь просматривать команду
    private function canAccessTeam($user, $team)
    {
        // Админ видит все
        if ($user->role == User::ROLE_ADMIN) {
            return true;
        }

        // Тимлид видит свои команды
        if ($user->role == User::ROLE_TEAMLEAD && $team->teamlead_id == $user->id) {
            return true;
        }

        // Сотрудник видит команды где он участник
        if ($user->role == User::ROLE_EMPLOYER && $team->isMember($user->id)) {
            return true;
        }

        return false;
    }

    // Может ли пользователь управлять командой
    private function canManageTeam($user, $team)
    {
        // Админ может управлять всеми
        if ($user->role == User::ROLE_ADMIN) {
            return true;
        }

        // Тимлид может управлять только своей командой
        if ($user->role == User::ROLE_TEAMLEAD && $team->teamlead_id == $user->id) {
            return true;
        }

        return false;
    }
}