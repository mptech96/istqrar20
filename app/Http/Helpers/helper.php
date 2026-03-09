<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\V1\Attachment;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

if (!function_exists('__tr')) {

    function __tr($key, $locale = null)
    {
        if($locale == null){
            $locale = \App::getLocale();
       }

        $langPath = base_path('lang');

        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $filePath = $langPath . "/{$locale}.json";

        if (!File::exists($filePath)) {
            File::put($filePath, json_encode([]));
        }

        // قراءة الملف الأصلي
        $translations = json_decode(File::get($filePath), true);

        // تحويل كل المفاتيح إلى lowercase
        $normalizedTranslations = [];
        $needsRewrite = false;

        foreach ($translations ?? [] as $existingKey => $value) {
            $lowerKey = mb_strtolower($existingKey);
            if ($lowerKey !== $existingKey) {
                $needsRewrite = true;
            }

            // نحتفظ بأحدث قيمة في حال التكرار
            $normalizedTranslations[$lowerKey] = $value;
        }

        // المفتاح الحالي
        $normalizedKey = mb_strtolower($key);

        // إذا لم يكن المفتاح موجودًا نضيفه
        if (!array_key_exists($normalizedKey, $normalizedTranslations)) {
            $normalizedTranslations[$normalizedKey] = $key;
            $needsRewrite = true;
        }

        // نكتب الملف فقط إذا كان هناك تغيير
        if ($needsRewrite) {
            File::put($filePath, json_encode($normalizedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $normalizedTranslations[$normalizedKey];
    }

}

if (! function_exists('save_file_req')) {
    
    function save_file_req($request, $fileName, $path, $name = null)
    {
        //save_file_req($request, 'files', base_path('public/uploads/files/'), 'file.pdf');
        try {
            if($request->hasFile($fileName)){
                $file = $request->file($fileName);
                if($name == null){
                    $name = time() . $file->getClientOriginalName();
                }
                $file->move($path, $name);
                return $name;
            }
        } catch (\Exception $e) {}
        return null;
    }
    
}

if (! function_exists('__saveFile')) {
    
    function __saveFile($file, $path, $name = null)
    {
        try {
            if($file){
                if($name == null){
                    $name = time() . $file->getClientOriginalName();
                }
                $file->move($path, $name);
                return $name;
            }
        } catch (\Exception $e) {}
        return null;
    }
    
}

if (! function_exists('delete_file_req')) {
    
    function delete_file_req($fileName, $path)
    {
        try {
            $path = $path . $fileName;
            if (File::exists($path)) {
                File::delete($path);
                return true;
            }
        } catch (\Exception $e) {}
        return false;
    }
    
}

if (! function_exists('toArrayObject')) {
    
    function toArrayObject($input) {
        // إذا كان مصفوفة أو كائن
        if (is_array($input) || is_object($input)) {
            $array = [];
    
            // إذا كان كائن، نحول خصائصه إلى مصفوفة
            if (is_object($input)) {
                foreach ($input as $key => $value) {
                    $array[$key] = toArrayObject($value);
                }
            } else {
                // إذا كان مصفوفة
                foreach ($input as $key => $value) {
                    $array[$key] = toArrayObject($value);
                }
            }
    
            // تحويل النتيجة إلى ArrayObject مع الوصول كـ property
            return new ArrayObject($array, ArrayObject::ARRAY_AS_PROPS);
        }
    
        // إذا كان قيمة عادية، نرجعها كما هي
        return $input;
    }
    
}

if (!function_exists('____sql')) {
    
    function ____sql()
    {
        try{
            DB::unprepared(file_get_contents( base_path('sql/v1/database.sql')));
            //DB::unprepared(file_get_contents( base_path('sql/v1/settings_seeder.sql')));
        }catch (\Exception $e) {
            $e->getMessage();
        }
        
        try{
            if (! Schema::hasColumn('users', 'token') ) {
                DB::statement("ALTER TABLE users ADD COLUMN token VARCHAR(500) NULL DEFAULT NULL AFTER role;");
            //DB::unprepared(file_get_contents( base_path(sql\v1\000000_update_2026_03_10.sql')));

            }
        }catch (\Exception $e) {
            return $e->getMessage();
        }
        
        try{
            if (! Schema::hasColumn('ai_suggestions', 'metadata') ) {
                DB::statement("ALTER TABLE ai_suggestions ADD COLUMN metadata json NULL DEFAULT NULL AFTER processing_time;");
            }
        }catch (\Exception $e) {
            return $e->getMessage();
        }

        try{
            if (! Schema::hasColumn('visits', 'start_latitude') ) {
                DB::unprepared(file_get_contents( base_path('sql\v1\000000_update_2026_03_10.sql')));
            }
        }catch (\Exception $e) {
            return $e->getMessage();
        }
        
    }

}

if (!function_exists('__tr')) {

    function __tr($key, $locale = null)
    {
        if($locale == null){
            $locale = \App::getLocale();
       }

        $langPath = base_path('lang');

        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $filePath = $langPath . "/{$locale}.json";

        if (!File::exists($filePath)) {
            File::put($filePath, json_encode([]));
        }

        // قراءة الملف الأصلي
        $translations = json_decode(File::get($filePath), true);

        // تحويل كل المفاتيح إلى lowercase
        $normalizedTranslations = [];
        $needsRewrite = false;

        foreach ($translations ?? [] as $existingKey => $value) {
            $lowerKey = mb_strtolower($existingKey);
            if ($lowerKey !== $existingKey) {
                $needsRewrite = true;
            }

            // نحتفظ بأحدث قيمة في حال التكرار
            $normalizedTranslations[$lowerKey] = $value;
        }

        // المفتاح الحالي
        $normalizedKey = mb_strtolower($key);

        // إذا لم يكن المفتاح موجودًا نضيفه
        if (!array_key_exists($normalizedKey, $normalizedTranslations)) {
            $normalizedTranslations[$normalizedKey] = $key;
            $needsRewrite = true;
        }

        // نكتب الملف فقط إذا كان هناك تغيير
        if ($needsRewrite) {
            File::put($filePath, json_encode($normalizedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $normalizedTranslations[$normalizedKey];
    }

}

if (! function_exists('saveAttachment')) {
    
    function saveAttachment($file, $object)
    {
        try {
            $manager = new ImageManager(new Driver());
            
            $mimeType  = $file->getClientMimeType();
            $size      = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            $original  = $file->getClientOriginalName();
        
            
            $width = null;
            $height = null;
            if (str_starts_with($mimeType, 'image/')) {
                $image  = $manager->read($file->path());
                $width  = $image->width();
                $height = $image->height();
            }
            
            $file_name = __saveFile($file, base_path('public/uploads/attachments/'));
        
            Attachment::create([
                // الربط الديناميكي
                'entity_type'   => get_class($object),
                'entity_id'     => $object->id,
        
                // الملف
                'original_name' => $original,
                'file_name'     => $file_name,
                'file_path'     => base_path('public/uploads/attachments/' . $file_name),
                'file_url'      => asset('uploads/attachments/' . $file_name),
        
                // المواصفات
                'file_type'     => Str::startsWith($mimeType, 'image/') ? 'image' : 'file',
                'mime_type'     => $mimeType,
                'file_size'     => $size,
                'file_extension'=> $extension,
        
                // للصور
                'width'         => $width,
                'height'        => $height,
        
                // المالك
                //'uploaded_by'   => Auth::id(),
                'uploaded_by'   => 1,
                'upload_source' => 'web',
        
                // الحالة
                'is_public'     => 0,
                'is_active'     => 1,
            ]);


        } catch (\Exception $e) {}
    }
    
}

if (! function_exists('overWriteEnvFile')) {
    
    function overWriteEnvFile($type, $val)
    {
        try {
            $path = base_path('.env');
            if (file_exists($path)) {
                $val = '"' . trim($val) . '"';
                if (is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0) {
                    file_put_contents($path, str_replace(
                        $type . '="' . env($type) . '"',
                        $type . '=' . $val,
                        file_get_contents($path)
                    ));
                } else {
                    file_put_contents($path, file_get_contents($path) . "\r\n" . $type . '=' . $val);
                }
            }
        } catch (\Exception $e) {}
    }
    
}


if (!function_exists('setting')) {
    /**
     * الحصول على قيمة إعداد من قاعدة البيانات
     * 
     * @param string $key مفتاح الإعداد
     * @param mixed $default القيمة الافتراضية إذا لم يوجد الإعداد
     * @param string $group مجموعة الإعداد (اختياري)
     * @return mixed
     */
    function setting($key, $default = null, $group = 'general')
    {
        return \App\Models\V1\Setting::get($key, $default, $group);
    }
}

if (!function_exists('set_setting')) {
    /**
     * تعيين قيمة إعداد في قاعدة البيانات
     * 
     * @param string $key مفتاح الإعداد
     * @param mixed $value القيمة
     * @param string $group مجموعة الإعداد
     * @return \\App\Models\V1\Setting
     */
    function set_setting($key, $value, $group = 'general')
    {
        return \App\Models\V1\Setting::set($key, $value, $group);
    }
}

if (!function_exists('settings_group')) {
    /**
     * الحصول على جميع إعدادات مجموعة معينة
     * 
     * @param string $group اسم المجموعة
     * @param bool $publicOnly العودة فقط للإعدادات العامة
     * @return array
     */
    function settings_group($group, $publicOnly = false)
    {
        return \App\Models\V1\Setting::getGroup($group, $publicOnly);
    }
}

if (!function_exists('has_setting')) {
    /**
     * التحقق من وجود إعداد
     * 
     * @param string $key مفتاح الإعداد
     * @param string $group مجموعة الإعداد
     * @return bool
     */
    function has_setting($key, $group = 'general')
    {
        return \App\Models\V1\Setting::has($key, $group);
    }
}

if (!function_exists('remove_setting')) {
    /**
     * حذف إعداد
     * 
     * @param string $key مفتاح الإعداد
     * @param string $group مجموعة الإعداد
     * @return bool
     */
    function remove_setting($key, $group = 'general')
    {
        return \App\Models\V1\Setting::remove($key, $group);
    }
}