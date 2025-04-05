<?php
namespace App\Enum;

enum StatusEnum: string
{
     case Active = 'active';
     case Non_Active = 'non_active';

     public function word(): string
     {
          return str($this->name)->replace('_', ' ')->title()->__toString();
     }

}