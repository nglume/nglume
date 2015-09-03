<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BaseSeeder extends Seeder
{

    /**
     * Get random elements from collection, always returning a collection
     * @param Collection $collection
     * @param null $count
     * @return Collection|mixed
     */
    public function randomElements(Collection $collection, $count = null)
    {
        if (is_null($count)){
            $count = rand(0, $collection->count());
        }

        switch ($count){
            case 0:
                return new Collection();
                break;
            case 1:
                return new Collection([$collection->random()]);
                break;
            default:
                return $collection->random($count);
        }
    }

}
