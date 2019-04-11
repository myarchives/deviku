<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Setting;
class ViuController extends Controller
{
    public function boot()
    {
        //set whatever level you want
        error_reporting(E_ALL ^ E_NOTICE);
    } 
    //
    function index(){
        return view("viu.index");
    }
    function getData(Request $request){
        $jwt="Bearer eyJhbGciOiJBMTI4S1ciLCJlbmMiOiJBMTI4Q0JDLUhTMjU2In0.oIdYGTM1hbluGV8uQQnp1too4qz-ASAKarVla0efU2ZG4YFt00UMOw.57o-H-1CbmUSus8lbfr9NA.QtsX1o5KkYO0wtx8kElPAWLt3lTd9vfLmMP7EGRu_j-03W7wwQsFcFE54MVRu3XpJ-ZhVn9vrxIDutWOIU8ceTMj9krjPTG1Ax6zWUQCc61IHkA2Bc-w3-n00pjHq_iZC7AUnL8x1xnZRvZ2GuCgtrgCoigicu4PNqgJGGn62gHh4f0W7GMEZic4BYl3fECVvIexcn1iriANGPdaxSYI3g.cKOUX4pXB04kfdoOLdlknQ";
        $start = $request->input('inputStartEp');
        $end = $request->input('inputEndEp');
        if($start == null){
            $start="0";
        }
        if($end == null){
            $end="150";
        }
        $settingData = Setting::find(1);
        switch($request->input("id")){
            case "senin":
                $id_hari =$settingData->viuSenin;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "selasa":
                $id_hari =$settingData->viuSelasa;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "rabu":
                $id_hari =$settingData->viuRabu;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "kamis":
                $id_hari =$settingData->viuKamis;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "jumat":
                $id_hari =$settingData->viuJumat;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "sabtu":
                $id_hari =$settingData->viuSabtu;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            case "minggu":
                $id_hari =$settingData->viuMinggu;
                $result= $this->curl_viu($id_hari, $jwt,$start,$end);
            break;
            default:
                $result= $this->curl_viu($request->input("id"), $jwt, $start, $end);
        }
        $number = json_decode($result,true);
        $data = $number['response']['container']['item'];
        if(empty($data)){
            return response('Hello World', 404);
        }
        return $this->data($data);
    }
    function data($result){
        $path_sub=".";
        $subtext = "'FontSize=22,PrimaryColour=&H00FFFF&'";
        $command_ffmpeg = '-i "\ffmpeg\blue.png" -filter_complex "[0:v]scale=1280x720[outv];[outv][1:v]overlay=10:10[outw];[outw]subtitles=sub/%%~na.ass:force_style='.$subtext.'[out]" -map "[out]" -map 0:a -aspect 16:9 -c:a copy -bsf:a aac_adtstoasc -c:v libx264 -movflags +faststart -pix_fmt yuv420p -preset fast -b:v 900K';
        $hardsub360p = ' && "'.$path_sub.'\ffmpeg\ffmpeg.exe" -y -i "'.$path_sub.'\hardsub\%%~na-720p.mp4" -aspect 16:9 -c:a copy -s 640x360 -bsf:a aac_adtstoasc -c:v libx264 -movflags +faststart -pix_fmt yuv420p -crf 27 -preset faster "I:\hardsub\%%~na-360p.mp4" && MOVE "'.$path_sub.':\%%a" "'.$path_sub.':\RAW\%%a"';
        
        $sub = "";
        $subtitle_code ="";
        $ffmpeg_code = "";
        foreach($result as $item){
            if(!empty($item["subtitle_id_srt"])){
                $subtitle_indo = $item["subtitle_id_srt"];
                $slug =$item["slug"];
                $slug = $this->seoUrl($slug);
                $subtitle_code .= "wget '".$subtitle_indo."' -OutFile 'sub/".$slug.".srt' \n";
                $ffmpeg_code .= " \n".''.$path_sub.'\ffmpeg\ffmpeg.exe -y -i "'.$item["href"].'" -c copy '.$path_sub.'\\'.$slug.'.ts;';
                //$ffmpeg_code .= " \n".'start "Encoding '.$slug.'" powershell.exe "'.$path_sub.':\ffmpeg\ffmpeg.exe" -y -i "'.$items["href"].'" '.$command_ffmpeg.' "'.$path_sub.':\\'.$slug.'.720p.mp4"'.$hardsub360p;
                //$subbes= $slug." Sub<br>";
            }
        }
        return response($subtitle_code.$ffmpeg_code, 200);
    }
    function curl_viu($id, $jwt, $start, $end){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.viu.com/ott/web/api/container/load?id=".$id."&start=$start&limit=$end&geofiltered=false&contentCountry=ID&contentFlavour=all&regionid=all&languageid=id&ccode=ID&geo=10&fmt=json&ver=1.0&aver=5.0&appver=2.0&appid=viu_desktop&platform=desktop&iid=55d231f6-4851-4733-a53d-7aab18c8bab8",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 300,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Postman-Token: a00e8e0f-1951-4049-bd14-22b24681f878",
            "Authorization: ".$jwt,
            "Pragma: no-cache"
          ),
           CURLOPT_SSL_VERIFYPEER => false,
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
          $data ="cURL Error #:" . $err;
        } else {
          $data= $response;
        }
        return $data;
    }
    function seoUrl($string) {
        $string = trim($string); // Trim String
    
        $string = strtolower($string); //Unwanted:  {UPPERCASE} ; / ? : @ & = + $ , . ! ~ * ' ( )
    
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);  //Strip any unwanted characters
    
        $string = preg_replace("/[\s-]+/", " ", $string); // Clean multiple dashes or whitespaces
    
        $string = preg_replace("/[\s_]/", "-", $string); //Convert whitespaces and underscore to dash
    
        return $string;
    
    }
}
