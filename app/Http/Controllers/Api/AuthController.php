<?php

namespace App\Http\Controllers\Api;

use DB;
use App\User;
use Validator;
use App\Therapist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
	/**
     * Login API
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();

			$response = [
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
				'role' => $user->role,
				'email' => $user->email,
				'token' => $user->createToken('MyApp')->accessToken
			];

			return response()->json(['data' => $response], 200);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

	/**
     * Register API
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
			'email' => 'required|email|unique:users',
            'role' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

		DB::beginTransaction();

        $user = User::create([
            'first_name'=>$request->get('first_name'),
            'last_name'=>$request->get('last_name'),
            'email'=>$request->get('email'),
            'password'=>bcrypt($request->get('password')),
            'role'=>$request->get('role'),
        ]);

		DB::commit();

		$response = [
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'role' => $user->role,
			'email' => $user->email,
			'token' => $user->createToken('MyApp')->accessToken
		];

        return response()->json(['data' => $response], 200);
    }

    /**
     * Endpoint specifically for registering a therapist
     * This endpoint firstly creates a user account and
     * associates that user account with a therapist account
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function registerTherapist(Request $request)
    {
        $validate = Validator::make($request->all(),[
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'fee_per_hour' => 'required|digits_between:0,1000000',
            'years_of_experience' => 'numeric',
            'availability' => ['required',Rule::in([true,false])],
            'name_of_practice' => 'required|string',
            'office_phone' => 'required|min:10',
            'address_line_1' => 'required|string|min:3',
            'city' => 'required|string|min:3',
            'state' => 'required|string|min:3',
            'country' => 'required|string|min:3',
            'personal_pronouns' => 'required|string|min:3',
            'type_of_therapist' => 'required',
            'type_of_license' => 'required',
			'year_licensed' => 'required',
            'years_of_experience' => 'required',
            'personal_statement' => 'required|string|min:10',
            'practice_website' => 'required|url',
        ]);

        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 401);
        } else {
			DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->get('password')),
                'role' => 'therapist',
            ]);

            $data = $request->except(['first_name','last_name','email','password', 'role']);
            $data['user_id'] = $user->id;
            $therapist = Therapist::create($data);

			DB::commit();

			$response = [
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
				'role' => $user->role,
				'email' => $user->email,
				'therapist_id' => $therapist->id,
				'token' => $user->createToken('MyApp')->accessToken
			];

            return response()->json(['data' => $response], 200);
        }
    }

	/**
     * Returns the details of a logged in user
     *
     * @return \Illuminate\Http\Response
     */
    public function details()
    {
        $user = Auth::user();

        return response()->json(['data' => $user], 200);
    }

	/**
     * Log user out
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        DB::table('oauth_access_tokens')
	        ->where('user_id', Auth::user()->id)
	        ->update(['revoked' => true]);

        return response()->json(['status' => true]);
    }
}
