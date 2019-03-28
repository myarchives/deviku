<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Country;
use App\Drama;
use App\Content;
use Cache;
use App\Setting;
use App\Type;
use Yajra\DataTables\Facades\DataTables;
class GDController extends Controller
{
    //

    use HelperController;
    public function AdminToken(){
        $settingData = Setting::find(1);
        $tokenDriveAdmin = $settingData->tokenDriveAdmin;
        $resultCurl = $this->get_token($tokenDriveAdmin);
        
        return $resultCurl;
    }
    public function singkronFolder(){
        
        $resultCurl['files']  = null;
        $settingData = Setting::find(1);
        $oldFolder = $settingData->folder720p;
        $resultCurl = $this->singkronfile($oldFolder);
        $fdrive =array(); 
        foreach($resultCurl['files'] as $Nofiles){
            if(preg_match('/[[\d]+]/', $Nofiles['name'], $output_array)){
                $url = str_replace(array('[',']'),'', $output_array[0]);
                $content = Drama::where('id',$url)->first();
                if($content){
                    if($content->folderid == "a"|| $content->folderid !=$Nofiles['id']){
                        $content->folderid = $Nofiles['id'];
                        array_push($fdrive,$content->title);
                    }
                    $content->save();
                
                }
            }

        }
        $value = Drama::with('country')->with('type')->with('eps')->orderBy('id','desc')->get();
        Cache::forever('Drama',$value);
        //return dd($fdrive);
        return view('dashboard.singkronContent')->with('url', $fdrive); 

    }
    public function singkron($id){
        $settingData = Setting::find(1);
        $oldFolder = $settingData->folderUpload;
        //$resultCurl = $this->singkronfile($folderId);
        $resultCurl = $this->singkronfile($oldFolder);
        $fdrive =array(); 
        foreach($resultCurl['files'] as $Nofiles){
            if(preg_match("/-720p.mp4/",$Nofiles['name'])){
                $url = str_replace('-720p.mp4','', $Nofiles['name']);
                $content = Content::where('url', $url)->first();
                if($content){
                    if(Cache::get('Drama')){
                        $value =Cache::get('Drama')->where('id',$content->drama_id)->first();
                    }else{
                        $value = Drama::with('country')->with('type')->with('eps')->orderBy('id','desc')->get();
                        Cache::forever('Drama',$value);
                        $value = Cache::get('Drama')->where('id',$content->drama_id)->first();
                    }
                    if($value){
                        $folderId= $value->folderid;
                    }else{
                        $folderId= $id;
                    }
                    $this->GDMoveFolder($Nofiles['id'],$folderId);
                    if($content->f720p !="https://drive.google.com/open?id=".$Nofiles['id'] ){
                        $content->f720p = "https://drive.google.com/open?id=".$Nofiles['id'] ;
                        if(is_null($content->f360p)){
                            $content->f360p = "https://drive.google.com/open?id=".$Nofiles['id'] ;
                        }
                        $content->save();
                        Drama::find($content->drama_id)->touch();
                        $data = Content::orderBy('id','desc')->where('drama_id',$id)->get();
                        Cache::forever('Content'.$id,$data);
                        array_push($fdrive,$url);
                    }
                }
            }elseif(preg_match("/-360p.mp4/",$Nofiles['name'])){
                $url = str_replace('-360p.mp4','', $Nofiles['name']);
                $content = Content::where('url', $url)->first();
                
                if($content){
                    if(Cache::get('Drama')){
                        $value =Cache::get('Drama')->where('id',$content->drama_id)->first();
                    }else{
                        $value = Drama::with('country')->with('type')->with('eps')->orderBy('id','desc')->get();
                        Cache::forever('Drama',$value);
                        $value = Cache::get('Drama')->where('id',$content->drama_id)->first();
                    }
                    if($value){
                        $folderId= $value->folderid;
                    }else{
                        $folderId= $id;
                    }
                    $this->GDMoveFolder($Nofiles['id'],$folderId);

                    if($content->f360p !="https://drive.google.com/open?id=".$Nofiles['id'] ){
                       $content->f360p = "https://drive.google.com/open?id=".$Nofiles['id'] ;
                        if(is_null($content->f720p)){
                            $content->f720p = "https://drive.google.com/open?id=".$Nofiles['id'] ;
                        }
                        $content->save();
                        Drama::find($content->drama_id)->touch();
                        $data = Content::orderBy('id','desc')->where('drama_id',$id)->get();
                        Cache::forever('Content'.$id,$data);
                        array_push($fdrive,$url);
                    }
                }
            }                 
        }
        return view('dashboard.singkronContent')->with('url', $fdrive); 
    }
}
