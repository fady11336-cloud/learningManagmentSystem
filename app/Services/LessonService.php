<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
class LessonService
{
    /**
     * Create a new class instance.
     */

    public function createLesson(Course $course, array $data) 
    {
        $course->lessons()->where('order','>=',$data['order'])
        ->increment('order');

        return $course->lessons()->create($data);
    }

    public function updateLesson(Lesson $lesson, array $data)
    {
        return DB::transaction(function() use($lesson,$data)
        {
            $oldOrder = $lesson->order;
            $newOrder = $data['order'];

            if($oldOrder < $newOrder)
                {
                    $lesson->course->lessons()->whereBetween(
                        'order',[$oldOrder + 1, $newOrder]
                    )->decrement('order');
                }
            elseif($oldOrder > $newOrder)
                {
                    $lesson->course->lessons()->whereBetween(
                        'order',[$newOrder, $oldOrder - 1]
                    )->increment('order');
                }

            $lesson->update($data);

            return $lesson;
        });
    }

    public function deleteLesson(Lesson $lesson)
    {
        return DB::transaction(function()use($lesson)
        {
            $order = $lesson->order;

            $lesson->delete();
            
            $lesson->course->lessons()->where('order','>',$order)->decrement('order');

        });
    }


}
