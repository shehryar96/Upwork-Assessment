<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AccountOTP;
use App\Http\Resources\UserResource;
use Auth;
use Validator;
use Exception;
use Carbon\Carbon;
use Image;
use Storage;

class UserController extends Controller
{
    public function createUser(Request $request){

        try{
        
            // receiving request
            $name = isset($request->name) ? $request->name : '';
            $email = isset($request->email) ? $request->email : '';
            $password = isset($request->password) ? bcrypt($request->password) : '';
            $role = isset($request->role) ? $request->password : '';

            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            $user->role = $role;
            $user->save();
            
            return $user;
            
            return new UserResource($users);


        }
        catch(Exception $e)
        {
            return response()->json('error',$e->getMessage());
        }

    }

    public function InviteUser(Request $request){

        if($request->CLIENTID == env('client_id') && $request->CLIENTSECRET == env('client_secret'))
        {
            try{

                if(Auth::user()->role == 'admin')
                {
                    $validator = Validator::make($request->all(), [ 
                        'name' => 'required',
                        'email' => 'required|email|unique:users,email',
                        'role' => 'required',
                    ]);
        
        
                    if ($validator->fails()) {
                        $response = [
                            'message' => $validator->messages(),
                            'error' => 'true',
                            'status' => 200
                         ];
                        return response()->json(['response '=> $response]);
                    } 
                   
                    ##creating user with email & name
                    $name = $request->name;
                    $email = $request->email;
                    $role = $request->role;
    
                    $user = new user();
                    $user->name = $name;
                    $user->email = $email;
                    $user->role = $role;
                    $user->verified = 0;
                    $user->save();
                   
    
    
                    ##send user an invitation mail
                    \Mail::to($email)->send(new \App\Mail\InviteUser($user));
    
                    $response = [
                        'message' => 'Invitation Send successfully!',
                        'status' => 200,
                        'user_details' => $user
                    ];
    
                    return response()->json(['response' => $response]);

                }
                else{

                    $response = [
                        'message' => 'Only Admins Can send Invitation!',
                        'status' => 200,
                    ];
    
                    return response()->json(['response' => $response]);

                }

            }
            catch(Exception $e)
            {
                return response()->json(['Response'=>$e->getMessage()]);
            }
        }
        else{
            $error = [
                'error' => 'true',
                'message' => 'Request is not authorised',
                'status' => 401

            ];
            return response()->json(['Response'=>$error]);
        }

        

    }

    public function UserSignUp(Request $request,$id){

        if($request->CLIENTID == env('client_id') && $request->CLIENTSECRET == env('client_secret'))
        {
            try{

                $id = decrypt($id);

                 $validator = Validator::make($request->all(), [ 
                    'username' => 'required|min:4|max:20',
                    'password' => 'required|min:8',
                ]);

                
                if ($validator->fails()) {

                     $response = [
                        'message' => $validator->messages(),
                        'error' => true,
                        'status' => 200
                     ];
                    return response()->json(['response'=>$response]);
                }

                $username = $request->username;
                $password = bcrypt($request->password);


                $user = User::find($id);
                $user->username = $username;
                $user->password = $password;
                $user->registered_at = Carbon::now();
                $user->save();

            
                
                ##send four dight code to user
                $otp = rand(0,10000);

                $acc_otp['user_id'] = $id;
                $acc_otp['otp'] = $otp;
                $acc_otp['otp_status'] = 0;

            
            
                $save_otp = AccountOTP::updateOrCreate(['user_id'=>$id],$acc_otp);
                
              
                $details = [
                    'otp' => $otp,
                    'id' => $user->id,
                    'name' => $user->name
                ];
               
                
                \Mail::to($user->email)->send(new \App\Mail\UserVerificaionMail($details));

                $response = [
                    'message' => "Your Account Registerd Successfully! We have 4 digits otp on you email address ".$user->email.'. Please verify!',
                    'error' => false,
                    'status' => 200
                ];

             
                return response()->json(['Response' => $response]);


            }
            catch(Exception $e)
            {
                return response()->json('error',$e->getMessage());
            }
        }
        else{
            
            $error = [
                'error' => 'true',
                'message'=>'Request is not authorised!',
                'status' => 401

            ];
            return response()->json(['Response'=>$error]);
        }

    }

    public function verifyUser(Request $request,$id)
    {
        
        if($request->CLIENTID == env('client_id') && $request->CLIENTSECRET == env('client_secret'))
        {
            try{

                $id = decrypt($id);

                 $validator = Validator::make($request->all(), [ 
                    'otp' => 'required|digits:4',
                ]);

                if ($validator->fails()) {

                     $response = [
                        'message' => $validator->messages(),
                        'error' => 'true',
                        'status' => 200
                     ];
                    return response()->json(['response'=>$response]);
                }

              
                $otp = $request->otp;

                $acc_otp = AccountOTP::where('user_id',$id)->where('otp_status',0)->first();

                if($acc_otp->otp == $otp)
                {
                    $user = User::find($id);
                    $user->verified = 1;
                    $user->save();
                }
                else{

                    $response = [
                        'message' => 'Invalid OTP',
                        'error'=>true,
                        'status'=>200
                    ];

                    return response()->json(['Response' => $response]);

                }

                $response = [
                    'message' => 'Account has been verified successfully! You can now signIn.',
                    'error' => false,
                    'status' => 200
                ];
                
                return response()->json(['Response' => $response]);
            }
            catch(Exception $e)
            {
                return response()->json('error',$e->getMessage());
            }
        }
        else{
            
            $error = [
                'error' => 'true',
                'message'=>'Request is not authorised!',
                'status' => 401


            ];
            return response()->json(['Response'=>$error]);
        }

    }

    public function userSignIn(Request $request){
        if($request->CLIENTID == env('client_id') && $request->CLIENTSECRET == env('client_secret'))
        {
            try{

                $validator = Validator::make($request->all(), [ 
                    'email' => 'required',
                    'password' => 'required',
                ]);

                
                if ($validator->fails()) {

                     $response = [
                        'message' => $validator->messages(),
                        'error' => true,
                        'status' => 200
                     ];
                    return response()->json(['response'=>$response]);
                }

                $email = $request->email;
                $password = $request->password;

                if(Auth::attempt(['email' => $email, 'password' => $password])){ 

                    $user = Auth::user(); 
                    if($user->verified == 0)
                    {
                        Auth::logout();
                        $response = [
                            'message' => 'Your Account is not Verified! Please Verify account first!',
                            'error' => true,
                            'status' => 403
                        ];
                        
                        return response()->json(['Response' => $response]);
                    }

                    $token = $user->createToken('upwerkTest')->accessToken; 
                    
                    $response = [
                        'token' => $token,
                        'user_details' => new UserResource($user),
                        'message' => 'User SignIn successfully!',
                        'error' => false,
                        'status' => 200
                    ];
                        
                    return response()->json(['Response' => $response]);

                } 
                else{ 

                    $response = [
                        'message' => 'Invalid Credentials',
                        'error' => true,
                        'status' => 403
                    ];
                    
                    return response()->json(['Response' => $response]);
 
                } 

            }
            catch(Exception $e)
            {
                return response()->json('error',$e->getMessage());
            }
        }
        else{
            
            $error = [
                'error' => 'true',
                'message' => 'Request is not authorised!',
                'status' => 401
            ];
            return response()->json(['Response'=>$error]);
        }

    }

    public function updateProfile(Request $request){

        if($request->CLIENTID == env('client_id') && $request->CLIENTSECRET == env('client_secret'))
        {   
            try{

                if(Auth::user())
                {

                    $validator = Validator::make($request->all(), [ 
                        'profile_img' => 'image|mimes:jpeg,png,jpg'
                    ]);

                    if ($validator->fails()) {

                        $response = [
                           'message' => $validator->messages(),
                           'error' => true,
                           'status' => 200
                        ];
                       return response()->json(['response'=>$response]);
                   }
    

                    $id = Auth::user()->id;

                    $user = User::find($id);

                    if($request->has('username'))
                    {
                        $user->username = $request->username;
                    }

                    if($request->has('name'))
                    {
                        $user->name = $request->name;
                    }

                    if($request->hasfile('profile_img'))
                    {
                        $image = $request->profile_img;
                        $fileName = 'user_'.time() . '.' . $image->getClientOriginalExtension();
            
                        $img = Image::make($image->getRealPath());
                        $img->resize(256, 256, function ($constraint) {
                            $constraint->aspectRatio();                 
                        });
            
                        $img->stream();
            
                        Storage::disk('public')->put('user/profileImages'.'/'.$fileName, $img, 'public');
                        
                        $user->user_img = $fileName;
                    }

                    $user->save();

                    $response = [
                        'user_details' => new UserResource($user),
                        'message' => 'Profile Updated Successfully!',
                        'error' => false,
                        'status' => 200
                    ];
                        
                    return response()->json(['Response' => $response]);

                }
                else{

                    $response = [
                        'message' => 'Invalid Credentials',
                        'error' => true,
                        'status' => 403
                    ];
                    
                    return response()->json(['Response' => $response]);   
                }

            }
            catch(Exception $e)
            {
                return response()->json('error',$e->getMessage());
            }

        }   
        else{
            
            $error = [
                'error' => 'true',
                'message'=>'Request is not authorised!',
                'status' => 401
            ];
            return response()->json(['Response'=>$error]);
        }

    }
}
