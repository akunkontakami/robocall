<?php
namespace App\Enum;

enum Role: string
{
     case BA = "ba";
     case AM = "am";
     case SPV = "spv";
     case Agent = "agent";
     case QC = "qc";
     case Agent_Escalation = "agent_escalation";
     case SPV_Escalation = "spv_escalation";
     case Comm_Agent = "agent_comm";
     case Customer = "customer";
     case Admin = "admin";


     public function label(): string
     {
          if($this->value==='agent_escalation'){
               return 'Escalation Agent';
          }
          if($this->value==='spv_escalation'){
               return 'Escalation SPV';
          }
          return str($this->name)->replace('_', ' ')->__toString();
     }
}