<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function index(Request $request)
    {
        $response = null;
        $authenticatedUserType = $request->__authenticatedUser->user_type;
        $isAdmin = $authenticatedUserType == env('ADMIN_ROLE_ID');
        $isSuperAdmin = $authenticatedUserType == env('SUPERADMIN_ROLE_ID');

        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif ($isAdmin || $isSuperAdmin) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }
    // Define the variable $response outside the conditional blocks, initializing it with null. 
    // This initialization ensures that the variable is accessible later in the method, even if none of the conditions are met.
    // I've extracted the user_type check and simplified it with boolean variables.
    // This makes the code more concise and easier to read.


    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->except(['_token', 'submit']);
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, $data, $cuser);

        return response($response);
    }
    // The array_except method is replaced with the except method, that provides an updated approach 
    // for excluding specific keys from the request data. This makes the code a little easier to read.

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }
    // Removed unused variable

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $userId = $request->get('user_id');

        if ($userId) {
            $response = $this->repository->getUsersJobsHistory($userId, $request);
            return $response;
        }

        return null;
    }

    // Variable names are modified to follow naming conventions (camelCase).
    // The code structure is revised for improved readability.


    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }
    // removed the unused $data variable



    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $jobId = $data['job_id'] ?? null;
        $session = $data['session_time'] ?? '';
        $adminComment = $data['admin_comment'] ?? '';

        $manuallyHandled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $byAdmin = $data['by_admin'] === 'true' ? 'yes' : 'no';

        $flagged = $data['flagged'] === 'true' ? 'yes' : 'no';
        if ($flagged === 'yes' && $adminComment) {
            return "Please, add comment";
        }

        if ($time || $distance) {
            Distance::where('job_id', $jobId)->update(['distance' => $distance, 'time' => $time]);
        }

        if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {
            Job::where('id', $jobId)->update([
                'admin_comments' => $adminComment,
                'flagged' => $flagged,
                'session_time' => $session,
                'manually_handled' => $manuallyHandled,
                'by_admin' => $byAdmin,
            ]);
        }

        return response('Record updated!');
    }

    // Used ternary operators to simplify the setting of some of the variables.
    // Default values are assigned directly using the null coalescing operator (??).
    // The code structure is revised for improved readability and maintainability.
    // Removed unused variables while updating.
    // Variable names are modified to follow naming conventions (camelCase).


    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }


    public function resendNotifications(Request $request)
    {
        $jobId = $request->get('job_id');
        $job = $this->repository->find($jobId);
        $jobData = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $jobData, '*');

        return response(['success' => 'Push sent']);
    }

    // Variable names are modified to follow standard naming conventions (camelCase).
    // The code structure is revised for improved readability and maintainability.

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {

        $jobId = $request->get('job_id');
        $job = $this->repository->find($jobId);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

    // Removed unused code
    // Variable names are modified to follow standard naming conventions (camelCase).
    // The code structure is revised for improved readability and maintainability.
}
