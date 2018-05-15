<?php
namespace App\Classes;

class ApiError
{

    private $default_visualmsg = "Возникла ошибка ,извините за предоставленные неудобства";

    private $err_code,$err_techmsg,$visualmsg;

    public function __construct($err_code,$param_name = NULL,$visualmsg = NULL,$err_techmsg = NULL)
    {
        /*if(gettype($err_techmsg) === "array"){



        }*/
        $this->err_code = $err_code;
        $this->err_techmsg = $err_techmsg ?? $this->generateTechMessage($err_code,$param_name);
        $this->visualmsg = $visualmsg ?? $this->generateVisualMessage($err_code) ;

    }

    public function json()
    {

       return response()->json([
           "error" => [

               "error_code" => $this->err_code,
               "error_techmsg" => $this->err_techmsg,
               "error_visualmsg" => $this->visualmsg.'( Код: '.$this->err_code.")",

           ]
       ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function generateVisualMessage($err_code)
    {

        switch ($err_code) {
            case 299 :
                return $this->default_visualmsg;
                break;
            case 300 :
                return "Пользователь не зарегистрирован";
                break;
            case 301 :
                return $this->default_visualmsg;
                break;
            case 302 :
                return $this->default_visualmsg;
                break;
            case 303 :
                return "Требуется войти в приложение";
                break;
            case 304 :
                return "Сервер временно недоступен";
                break;
            case 305 :
                return $this->default_visualmsg;
                break;
            case 306 :
                return $this->default_visualmsg;
                break;
            case 307 :
                return $this->default_visualmsg ;
                break;
            case 308 :
                return "вы не можете выполнить данное действие";
                break;
            case 309 :
                return "пожалуйста ,войдите в приложение";
                break;
            case 310 :
                return $this->default_visualmsg;
                break;
            default :
                return $this->default_visualmsg;


        }

    }

    public function generateTechMessage($err_code,$param_name = NULL)
    {

        $param_name = $param_name ?? '';

        switch ($err_code) {
            case 299 :
                return "ошибка валидатора параметров запроса";
                break;
            case 300 :
                return "Для выполнения данного действия ,зарегистрируйтесь";
                break;
            case 301 :
                return "не передан токен разработчика";
                break;
            case 302 :
                return "неправильный токен разработчика";
                break;
            case 303 :
                return "не передан auth_token";
                break;
            case 304 :
                return "сервер временно недоступен";
                break;
            case 305 :
                return "не передан обязательный параметр ".$param_name;
                break;
            case 306 :
                return "недействительный токен устройства";
                break;
            case 307 :
                return " передан неправильный формат параметра ".$param_name ;
                break;
            case 308 :
                return "текущий пользователь не имеет прав на выполнение данного действия";
                break;
            case 309 :
                return "пользователь с таким auth_token не найден";
                break;
            case 310 :
                return "ошибка записи в базу данных";
                break;
            default :
                return "Передан неопознаный код ошибки";


        }

    }

}