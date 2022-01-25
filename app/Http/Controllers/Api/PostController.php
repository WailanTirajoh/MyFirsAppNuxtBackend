<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\ToastrMessage;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'posts' => PostResource::collection(Post::orderByDesc('created_at')->paginate(10)),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePostRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePostRequest $request)
    {
        $post = Auth::user()->posts()->save(new Post($request->validated()));

        if ($request->file) {
            $post->addMedia($request->file)->usingName(Str::random(20))->toMediaCollection((new Post)->mediaName);
        }

        $message = 'Post created by ' . Auth::user()->name;
        broadcast(new PostCreated(PostResource::make($post)))->toOthers();
        broadcast(new ToastrMessage($message));
        return response()->json([
            'message' => $message,
            'post' => PostResource::make($post),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePostRequest  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update($request->validated());

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => PostResource::make($post)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        if (!Auth::user()->id == $post->user->id) {
            throw ValidationException::withMessages([
                'message' => 'You are not authorized to do the action'
            ])->status(Response::HTTP_FORBIDDEN);
        }

        $post->delete();

        $message = 'Post deleted by ' . Auth::user()->name;

        broadcast(new PostDeleted(PostResource::make($post)))->toOthers();
        broadcast(new ToastrMessage($message));

        return response()->json([
            'message' => $message,
        ]);
    }
}
