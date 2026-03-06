<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandlePutRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('put')) {
            // التحقق من نوع المحتوى
            $contentType = $request->header('Content-Type');
            
            if (strpos($contentType, 'multipart/form-data') !== false) {
                // معالجة multipart/form-data
                $input = file_get_contents('php://input');
                
                // استخراج boundry
                preg_match('/boundary=(.*)$/', $contentType, $matches);
                $boundary = $matches[1] ?? '';
                
                if ($boundary) {
                    // تقسيم البيانات حسب boundary
                    $parts = explode('--' . $boundary, $input);
                    
                    foreach ($parts as $part) {
                        if (empty($part) || trim($part) == '--') continue;
                        
                        // استخراج اسم الحقل
                        if (preg_match('/name="([^"]+)"/', $part, $nameMatch)) {
                            $fieldName = $nameMatch[1];
                            
                            // استخراج القيمة
                            $value = preg_replace('/^.*?\r\n\r\n(.*?)\r\n$/s', '$1', $part);
                            
                            // إضافة إلى الـ request
                            $request->request->add([$fieldName => trim($value)]);
                        }
                    }
                }
            } else {
                // معالجة application/x-www-form-urlencoded
                $content = file_get_contents('php://input');
                parse_str($content, $data);
                $request->request->add($data);
            }
        }
    
    return $next($request);      
    }
}
