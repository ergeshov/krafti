<?php

namespace App\Controllers\Admin;

use App\Model\Course;
use App\Model\File;
use Illuminate\Database\Eloquent\Builder;
use Vesp\Controllers\ModelController;

class Courses extends ModelController
{
    protected $model = Course::class;
    protected $scope = 'courses';

    /**
     * @param Builder $c
     * @return mixed
     */
    protected function beforeGet(Builder $c): Builder
    {
        $c->with('cover:id,title,updated_at');
        $c->with('diploma:id,title,updated_at');
        $c->with('template');

        return $c;
    }

    /**
     * @param Builder $c
     *
     * @return Builder
     */
    protected function beforeCount(Builder $c): Builder
    {
        if ($query = trim($this->getProperty('query'))) {
            $c->where(static function (Builder $c) use ($query) {
                $c->where('title', 'LIKE', "%$query%");
                $c->orWhere('description', 'LIKE', "%$query%");
            });
        }
        if ($this->getProperty('combo')) {
            $c->select('id', 'title', 'price');
        }

        return $c;
    }

    /**
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount(Builder $c): Builder
    {
        if (!$this->getProperty('combo')) {
            $c->with('cover:id,title,updated_at');
            $c->with('diploma:id,title,updated_at');
            $c->with('video:id,preview');
        }

        return $c;
    }

    /**
     * @param Course $record
     *
     * @return bool|string
     */
    protected function beforeSave($record)
    {
        if (!$title = trim($this->getProperty('title'))) {
            return 'Вы должны указать название курса';
        }

        if (Course::query()->where(['title' => $title])->where('id', '!=', $record->id)->count()) {
            return 'Название курса должно быть уникальным';
        }

        if (!trim($this->getProperty('category'))) {
            return 'Вы должны указать категорию курса';
        }

        if (!$age = trim($this->getProperty('age'))) {
            return 'Вы должны указать возраст аудитории курса';
        }

        if (!preg_match('#(\d+)-(\d+)#', $age)) {
            return 'Неверный формат возраста. Укажите 2 числа: от и до, через дефис.';
        }

        if ($cover = $this->getProperty('new_cover', $this->getProperty('cover'))) {
            if (is_array($cover) && !empty($cover['file'])) {
                if (!$file = $record->cover) {
                    $file = new File();
                }

                if ($file->uploadFile($cover['file'], $cover['metadata'])) {
                    $record->cover_id = $file->id;
                }
            }
        }

        if ($diploma = $this->getProperty('new_diploma', $this->getProperty('diploma'))) {
            if (is_array($diploma) && !empty($diploma['file'])) {
                if (!$file = $record->diploma) {
                    $file = new File();
                }

                if ($file->uploadFile($diploma['file'], $diploma['metadata'])) {
                    $record->diploma_id = $file->id;
                }
            }
        }

        return true;
    }
}
