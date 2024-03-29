<?php

namespace app\models;

use yii\db\ActiveQuery;
use app\models\forms\CalendarUploadedForm;

/** 
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $access_token
 * @property event[]
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
   public static function tableName() {
        return 'users';
   }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return self::findOne(['id' => $id]);

    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {   
        return self::findOne(['access_token' => $token]);
        
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return self::findOne(['username' => $username] );
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->access_token;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->access_token === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $this->saltPassword($password);
    }

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['password', 'string', 'length' => [6,40]],
            ['username', 'string', 'length' => [4,24]],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
       return ['username' => 'Пользователь',
               'password' => 'Пароль'];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$this->password) {
            $this->password  =  $this->saltPassword($this->password);
        }

        if (!$this->access_token) {
            $this->access_token = \Yii::$app->security->generateRandomString();
        }

        return true;
    }

    public function saveImage($filename) {
        $this->image = $filename;

        return $this->save(false);
    }

    public function deleteImage() {

        $imageUploadModel = new CalendarUploadedForm();
        $imageUploadModel->deleteCurrentFile($this->image);
    }

    public function getImage() {

        return ($this->image) ? '@web/' . 'uploads/image/' . $this->image : '@web/' . 'uploads/image/no_image.png';
    }

    public function beforeDelete()
    {
        $this->deleteImage();
        return parent::beforeDelete();
    }
    
    private function saltPassword(string $password) {
        return md5($password);
    }

    public function getNotes() {
        return $this->hasMany(Calendar::class, ['author_id' => 'id']);
    }
}
