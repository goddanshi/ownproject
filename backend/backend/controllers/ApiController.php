<?php
namespace backend\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\LoginForm;
use common\models\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;


class ApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://81.19.136.133', 'http://localhost:5173'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        return $behaviors;
    }

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    // POST /api/register
    public function actionRegister()
    {
        $data = Yii::$app->request->post();

        $user = new User();
        $user->username = $data['username'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->setPassword($data['password'] ?? '');
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->created_at = time();
        $user->updated_at = time();

        if ($user->save()) {
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Registration failed',
            'errors' => $user->errors
        ];
    }

    // POST /api/login
    public function actionLogin()
    {
        $data = Yii::$app->request->post();

        $model = new LoginForm();
        $model->username = $data['username'] ?? '';
        $model->password = $data['password'] ?? '';

        if ($model->login()) {
            $user = Yii::$app->user->identity;

            $config = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::plainText('your_secret_key_here')
            );

            $time = time();

            $token = $config->builder()
                ->issuedBy('http://81.19.136.133')
                ->permittedFor('http://81.19.136.133')
                ->issuedAt(\DateTimeImmutable::createFromFormat('U', $time))
                ->expiresAt(\DateTimeImmutable::createFromFormat('U', $time + 3600*24*7))
                ->withClaim('uid', $user->id)
                ->getToken($config->signer(), $config->signingKey());

            return [
                'success' => true,
                'token' => $token->toString(),
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid username or password',
            'errors' => $model->errors
        ];
    }

    // GET /api/check
    public function actionCheck()
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Missing token'];
        }

        $tokenStr = $matches[1];

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('your_secret_key_here')
        );

        try {
            $token = $config->parser()->parse($tokenStr);
            $claims = $token->claims();
            $uid = $claims->get('uid');

            $user = User::findOne($uid);
            if ($user) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        return ['success' => false];
    }


    // POST /api/logout
    public function actionLogout()
    {
        return ['success' => true, 'message' => 'Logout (client side only)'];
    }
}
