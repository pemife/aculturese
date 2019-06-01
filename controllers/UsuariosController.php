<?php

namespace app\controllers;

use app\models\Usuarios;
use app\models\UsuariosSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * UsuariosController implements the CRUD actions for Usuarios model.
 */
class UsuariosController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
              'class' => AccessControl::classname(),
              'only' => ['update', 'login', 'logout'],
              'rules' => [
                [
                  'allow' => true,
                  'actions' => ['update'],
                  'roles' => ['@'],
                  /*'matchCallback' => function ($rule, $action) {
                      return Yii::$app->user->id === 1;
                  },*/
                ],
                [
                  'allow' => true,
                  'actions' => ['login'],
                  'roles' => ['?'],
                ],
                [
                  'allow' => true,
                  'actions' => ['logout'],
                  'roles' => ['@'],
                ],
              ],
            ],
        ];
    }

    /**
     * Lists all Usuarios models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsuariosSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Usuarios model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Usuarios model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Usuarios();

        $model->scenario = Usuarios::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post())) {
            $model->token = $model->creaToken();
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Usuarios model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->tienePermisos($model)) {
            $model->scenario = Usuarios::SCENARIO_UPDATE;

            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $model->password = '';

            return $this->render('update', [
              'model' => $model,
            ]);
        }
        Yii::$app->session->setFlash('danger', 'No puedes modificar el perfil de otra persona');
        return $this->goHome();
    }

    public function actionModperfil($id)
    {
        $model = $this->findModel($id);

        if ($this->tienePermisos($model)) {
            $model->scenario = Usuarios::SCENARIO_MODPERFIL;

            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $model->password = '';

            return $this->render('modificarPerfil', [
                'model' => $model,
            ]);
        }
        Yii::$app->session->setFlash('danger', 'No puedes modificar el perfil de otra persona');
        return $this->goHome();
    }

    /**
     * Deletes an existing Usuarios model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($this->tienePermisos($model)) {
            $model->delete();

            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash('danger', 'No puedes borrar el perfil de otra persona');
        return $this->goHome();
    }

    /**
     * Finds the Usuarios model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Usuarios the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Usuarios::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionRecupass()
    {
        if ($email = Yii::$app->request->post('email')) {
            // Si el email esta vinculado con un usuario
            if ($model = Usuarios::find()->where(['email' => $email])->one()) {
                $model->scenario = Usuarios::SCENARIO_UPDATE;
                Yii::$app->mailer->compose()
                ->setFrom('aculturese@gmail.com')
                ->setTo($email)
                ->setSubject('Recuperacion de contraseña')
                ->setHtmlBody('Para recuperar la contraseña, pulsa '
                . Html::a('aqui', Url::to(['usuarios/cambio-pass', 'id' => $model->id], true), [
                  'data-method' => 'POST', 'data-params' => [
                    'tokenUsuario' => $model->token,
                  ],
                ]))
                ->send();

                Yii::$app->session->setFlash('info', 'Se ha mandado el email');
            } else {
                Yii::$app->session->setFlash('error', 'No se ha encontrado una cuenta vinculada a ese email');
            }

            return $this->redirect(['site/login']);
        }
        $email = '';
        return $this->render('escribeMail');
    }

    public function actionCambioPass($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->post('tokenUsuario') !== $model->token) {
            Yii::$app->session->setFlash('error', 'Validación incorrecta de usuario');
            return $this->redirect(['site/login']);
        }

        $model->scenario = Usuarios::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('info', 'La contraseña se ha guardado correctamente');
            return $this->redirect(['site/login']);
        }

        $model->password = $model->password_repeat = '';

        return $this->render('cambioPass', [
            'model' => $model,
        ]);
    }

    public function tienePermisos($model)
    {
        return Yii::$app->user->id === 1 || Yii::$app->user->id === $model->id;
    }
}
