export interface User {
     id: string;
     name: string;
     company_id: string;
     role: string;
     avatar: string;
     company_name?:string
     user_company : {
         name : string
         code : string
         profile : string
     }
 }
 
 export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
     auth: {
         user: User;
     };
     flash: {
         error?: string;
         success?: string;
     },
 };
 