<?php

namespace App\Repositories\Auth;

use LaravelEasyRepository\Implementations\Eloquent;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Unit;
use App\Models\Foto_unit;
use Validator;
use App\Traits\BaseResponse;
use App\Helpers\Constants;
use App\Helpers\CommonUtil;
use App\Traits\BusinessException;
use Hash;
use App\Http\Controllers\BaseController;
use Image;
use Otp;
use App\Jobs\JobOtp;
use Illuminate\Support\Facades\Mail;
use App\Models\token;
use App\Models\Otps;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Jobs\JobSmsOtp;


class AuthRepositoryImplement extends Eloquent implements AuthRepository
{

    use BaseResponse;

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function login($request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw new BusinessException(401, 'Unauthorized!', Constants::HTTP_CODE_401);
        }

        $user = $this->model::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return self::buildResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            (['access_token' => $token, 'user' => $user]),
            CommonUtil::generateUUID()
        );
    }

    public function createThumbnail($path, $width, $height)
    {

        $img = (string) Image::make($path)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        })->encode();
        return $img;
    }
    public function register($request)
    {

        // return $convertNoHp;
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|string|email|max:255|unique:users',
        //     'phone' => 'required|unique:users',
        //     // 'password' => 'required|string|min:8'
        // ]);

        // if ($validator->fails()) {
        //     $dataFails=User::where('phone', '=', $request->phone)->first();
        //     throw new BusinessException(422, $dataFails, Constants::HTTP_CODE_422);
        // }

        try {
            $convertNoHp = CommonUtil::changeFormatPhoneTo62($request->phone);

            $data = User::select('phone')->where('phone', '=', $convertNoHp)->first();

                // if($data !== null )
                // {
                //     $dataUser = User::with('unit')->where('phone', '=', $convertNoHp)->first();

                //     return BaseController::success($dataUser, " User Sudah Mengirim Data", 200);
                // }


            $unit = Unit::create([
                // 'id_users' => $user->id ,
                'plate_no' => $request->plate_no,
                'brand_and_type' => $request->brand_and_type,
                'province_and_city' => $request->province_and_city,
                'vehicle_year' => $request->vehicle_year,
                'bpkb' => $request->bpkb,
                'rumah' => $request->rumah,
                'pajak' => $request->pajak,
            ]);

            $user = $this->model::create([
                'name' => $request->name,
                // 'email' => $request->email,
                'phone' => $convertNoHp,
                'role' => 3,
                'units_id' => $unit->id

            ]);


            $directFileDb = "https://agreesip.oss-ap-southeast-5.aliyuncs.com/agreesipdev/user/";
            $randomStringStnk = substr(sha1(rand()), 0, 5);
            $ossdirectFile = "agreesipdev/user/original/" . date("Y") . '/' . date("m");
            $thumbFile = "agreesipdev/user/thumb/" . date("Y") . '/' . date("m");
            $file_name = $randomStringStnk . '.' . $request['stnk']->getClientOriginalExtension();
            $tanda = ['original', 'thumb'];
            $stnkExtension = $request['stnk']->getClientOriginalExtension();
            $stnkThumbFile =  $this->createThumbnail($request['stnk'], 300, 300);
            // $stnkThumbFile=  $this->thumbnail($request['stnk']);

            $i = 0;
            for ($i = 0; $i < 2; $i++) {
                $savePicture = new Foto_unit();
                $savePicture->id_users =  $user->id;
                $savePicture->name = $tanda[$i] . '_' . 'stnk';
                $savePicture->sort = $i;
                $savePicture->path = $directFileDb . $tanda[$i] . '/' . date("Y") . '/' . date("m") . '/' .  $randomStringStnk . '.' . $stnkExtension;
                $savePicture->save();
            }

            $ossStnk = Storage::disk('oss')->putFileAs($ossdirectFile, $request['stnk'], $file_name, 'public');
            // https://agreesip.oss-ap-southeast-5.aliyuncs.com/agreesipdev/user/2022/08/9d715.jpg
            $ossStnk = Storage::disk('oss')->putFileAs($thumbFile, $request['stnk'], $file_name, 'public');



            // create random otp
            // $otp = mt_rand(100000, 999999);
            // // Insert otp to database
            // $tokenOtp = Token::firstOrNew(array('id_users' => $user->id));
            // $tokenOtp->token_type = 'Generate Password';
            // $tokenOtp->token = $otp;
            // $tokenOtp->timestamp = Carbon::now()->addMinutes(5);
            // $tokenOtp->save();
            // Send Mail

            // Mail::send('email.EmailOtp', ['Nama' => $user->name,  'otp' => $otp], function ($message) use ($request) {
            // $message->from('noreply@sitama.co.id', 'OTP TOKEN');
            // // $message->to($request['email']);
            // $message->to(['nspbdz@gmail.com']);
            // $message->subject('Verifikasi Registrasi [GROSIR MOBIL]');
            // });

            // $convertNoHp = CommonUtil::changeFormatPhoneTo62($request->phone);
            $otp_token = CommonUtil::generateOtp($request->phone, 6, 5);

            $textSms = "Hi $request->name, kode OTP Gadai Bpkb Online anda adalah $otp_token->token";
            dispatch(new JobSmsOtp($request->phone, $textSms));

            $newUser = User::with('unit')->where('id', '=', $user->id)->first();
        } catch (\Exception $e) {
            // return $detail;

            // DB::rollback();
            return BaseController::error(NULL, $e->getMessage(), 400);
        }
        // return $detail;
        return BaseController::success($newUser, "Sukses menambah Agent silahkan check sms anda untuk Mendapatkan OTP", 200);
    }




    public function profile()
    {
        $user = Auth::user();

        return self::buildResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            (['user' => $user]),
            CommonUtil::generateUUID()
        );
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return self::statusResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            'Success logout!!',
            CommonUtil::generateUUID()
        );
    }

    public function sendOtp($request)
    {
        // return 1;

        $otp = new Otp;

        $otp_token = $otp->generate($request->sms, 6, 5);

        $data = [
            'otp' => $otp_token->token
        ];

        // Find sms and send token
        // return $data;

        $user = $this->model::where('phone', $request->sms)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        // return $token;

        try {
            dispatch(new JobOtp($request->sms, $data));
        } catch (\Throwable $th) {
            throw $th;
        }

        return self::buildResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            $token,
            CommonUtil::generateUUID()
        );
    }
    // public function sendOtp($request)
    // {
    //     // return 1;

    //     $otp = new Otp;

    //     $otp_token = $otp->generate($request->email, 6, 5);

    //     $data = [
    //         'otp' => $otp_token->token
    //     ];

    //     // Find email and send token
    //     $user = $this->model::where('email', $request->email)->firstOrFail();
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     try {
    //         dispatch(new JobOtp($request->email, $data));
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }

    //     return self::buildResponse(
    //         Constants::HTTP_CODE_200,
    //         Constants::HTTP_MESSAGE_200,
    //         $token,
    //         CommonUtil::generateUUID()
    //     );
    // }

    public function OTPConfirm($request)
    {
        // return $request->id;
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $token = token::where('id_users', request('id'))->first();
        $user = User::find($request->id);
        // return $user;

        if ($user->status !== 1) {
            if ($token->token == request('otp')) {
                $status['active'] = 1;
                // $status['password'] = bycrypt($request->otp);
                $user->update($status);

                return BaseController::success($user->email, 'account has been activated', 200);
            } else {
                return BaseController::error(NULL, 'Wrong OTP Code !', 400);
            }
            return BaseController::success($user->email, 'Successfuly update password', 200);
        } else {
            return BaseController::success(NULL, 'User Already OTP Before', 400);
        }
        return BaseController::error(NULL, 'Unautorized', 400);
    }

    public function verifyOtp($request)
    {
        // return $request;

        $validator = Validator::make($request->all(), [
            // 'id' => 'required',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // $token = token::where('id_users',request('id'))->first();
        $token = Otps::where('token', '=', $request->otp)->first();

        $user = User::where('phone', '=', $token->identifier)->first();

        // return $token;
        // return $user;
        if ($user->is_active !== 1) {
            if ($token->token == request('otp')) {
                $status['is_active'] = 1;
                // $status['password'] = bycrypt($request->otp);
                $user->update($status);


                return BaseController::success($user->name, 'account has been activated', 200);
            } else {
                return BaseController::error(NULL, 'Wrong OTP Code !', 400);
            }
            return BaseController::success($user->name, 'Successfuly update password', 200);
        } else {
            return BaseController::success(NULL, 'User Already OTP Before', 400);
        }
        return BaseController::error(NULL, 'Unautorized', 400);
    }

    // Change password
    public function changePassword($request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            throw new BusinessException(422, $validator->messages(), Constants::HTTP_CODE_422);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            throw new BusinessException(422, 'Old password is incorrect', Constants::HTTP_CODE_422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return self::statusResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            'Success change password!!',
            CommonUtil::generateUUID()
        );
    }
    // End Change Password

    // Forgot Password
    public function forgotPassword($request)
    {
        $otp = new Otp;

        $otp_token = $otp->generate($request->email, 6, 5);

        $data = [
            'otp' => $otp_token->token
        ];

        try {
            dispatch(new JobOtp($request->email, $data));
        } catch (\Throwable $th) {
            throw $th;
        }

        return self::statusResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            'Success send otp!!',
            CommonUtil::generateUUID()
        );
    }

    public function verifyForgotPassword($request)
    {
        $otp = new Otp;

        $validate_token = $otp->validate($request->email, $request->otp);

        if (!$validate_token->status) {
            throw new BusinessException(422, $validate_token->message, Constants::HTTP_CODE_422);
        }

        return self::statusResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            $validate_token->message,
            CommonUtil::generateUUID()
        );
    }

    public function resetPassword($request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            throw new BusinessException(422, $validator->messages(), Constants::HTTP_CODE_422);
        }


        $user = $this->model::where('email', '=', $request->email)->first();
        if (empty($user)) {
            return self::statusResponse(
                Constants::HTTP_CODE_422,
                Constants::ERROR_MESSAGE_422,
                'Email not found!!',
                CommonUtil::generateUUID()
            );
        }

        try {
            $user->password = Hash::make($request->password);
            $user->save();
        } catch (\Throwable $th) {
            throw new BusinessException(422, $th, Constants::HTTP_CODE_422);
        }

        return self::statusResponse(
            Constants::HTTP_CODE_200,
            Constants::HTTP_MESSAGE_200,
            'Success reset password!!',
            CommonUtil::generateUUID()
        );
    }
    // End Forgot Password
}
