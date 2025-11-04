<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Folder model
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $parent_id
 * @property int $team_id
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 */
class Folder extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%folders}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['name', 'created_by'], 'required'],
            ['name', 'string', 'max' => 255],
            ['description', 'string'],
            [['parent_id', 'team_id', 'created_by'], 'integer'],
            ['team_id', 'exist', 'skipOnEmpty' => true, 'targetClass' => Team::class, 'targetAttribute' => 'id'],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            ['parent_id', 'exist', 'skipOnEmpty' => true, 'targetClass' => self::class, 'targetAttribute' => 'id'],
            // Проверка: папка не может быть родителем самой себе
            ['parent_id', 'validateParent'],
        ];
    }

    public function validateParent($attribute)
    {
        if ($this->parent_id == $this->id) {
            $this->addError($attribute, 'Папка не может быть родителем самой себе.');
        }

        // Проверка на циклическую вложенность
        if ($this->parent_id && $this->hasCircularReference($this->parent_id)) {
            $this->addError($attribute, 'Обнаружена циклическая ссылка в структуре папок.');
        }
    }

    /**
     * Проверка на циклическую ссылку
     */
    protected function hasCircularReference($parentId, $visited = [])
    {
        if (in_array($parentId, $visited)) {
            return true;
        }

        $visited[] = $parentId;
        $parent = self::findOne($parentId);

        if ($parent && $parent->parent_id) {
            return $this->hasCircularReference($parent->parent_id, $visited);
        }

        return false;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название папки',
            'description' => 'Описание',
            'parent_id' => 'Родительская папка',
            'team_id' => 'Команда',
            'created_by' => 'Создал',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    // Связь с родительской папкой
    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    // Связь с дочерними папками
    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id']);
    }

    // Связь с командой
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    // Связь с создателем
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    // Связь с проектами в этой папке
    public function getProjects()
    {
        return $this->hasMany(Project::class, ['folder_id' => 'id']);
    }

    /**
     * Получить все дочерние папки (рекурсивно)
     */
    public function getAllChildren()
    {
        $children = [];
        $directChildren = $this->children;

        foreach ($directChildren as $child) {
            $children[] = $child;
            $children = array_merge($children, $child->getAllChildren());
        }

        return $children;
    }

    /**
     * Получить путь к папке (хлебные крошки)
     */
    public function getPath()
    {
        $path = [$this];
        $current = $this;

        while ($current->parent) {
            $path[] = $current->parent;
            $current = $current->parent;
        }

        return array_reverse($path);
    }

    /**
     * Проверка: является ли пользователь участником команды папки
     */
    public function isParticipant($userId)
    {
        if (!$this->team) {
            return false;
        }

        return $this->team->isTeamlead($userId) || $this->team->isMember($userId);
    }

    /**
     * Получить все папки доступные пользователю
     */
    public static function getAccessibleFolders($user)
    {
        if (!$user) {
            return [];
        }

        // Админ видит все папки
        if ($user->role === 1) {
            return self::find()->with(['team', 'parent', 'children', 'projects'])->all();
        }

        // Получаем команды, в которых пользователь является участником или тимлидом
        $teamIds = [];

        // Команды где пользователь - тимлид
        $leadTeams = Team::find()->where(['teamlead_id' => $user->id])->select('id')->column();
        $teamIds = array_merge($teamIds, $leadTeams);

        // Команды где пользователь - участник
        $memberTeams = TeamMember::find()
            ->where(['user_id' => $user->id])
            ->select('team_id')
            ->column();
        $teamIds = array_merge($teamIds, $memberTeams);

        $teamIds = array_unique($teamIds);

        return self::find()
            ->where(['team_id' => $teamIds])
            ->with(['team', 'parent', 'children', 'projects'])
            ->all();
    }

    /**
     * Получить корневые папки (без родителя)
     */
    public static function getRootFolders($teamId = null)
    {
        $query = self::find()->where(['parent_id' => null]);

        if ($teamId) {
            $query->andWhere(['team_id' => $teamId]);
        }

        return $query->with(['children', 'projects'])->all();
    }

    /**
     * Получить дерево папок для команды
     */
    public static function getTree($teamId = null)
    {
        $roots = self::getRootFolders($teamId);
        $tree = [];

        foreach ($roots as $root) {
            $tree[] = self::buildTreeNode($root);
        }

        return $tree;
    }

    /**
     * Построить узел дерева
     */
    protected static function buildTreeNode($folder)
    {
        $node = [
            'id' => $folder->id,
            'name' => $folder->name,
            'description' => $folder->description,
            'type' => 'folder',
            'children' => [],
        ];

        // Добавляем дочерние папки
        foreach ($folder->children as $child) {
            $node['children'][] = self::buildTreeNode($child);
        }

        // Добавляем проекты
        foreach ($folder->projects as $project) {
            $node['children'][] = [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'type' => 'project',
                'tasks_count' => $project->getTasks()->count(),
            ];
        }

        return $node;
    }
}
