<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCommentCreated;
use App\Events\PostCommentDeleted;
use App\Events\TestPrivate;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Notifications\PostCommentedNotification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PostCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Post $post)
    {
        return response()->json([
            'message' => 'Get all comment success',
            'comments' => PostCommentResource::collection($post->comments()->orderBy('created_at')->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCommentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCommentRequest $request, Post $post)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::user()->id;

        $comment = $post->comments()->save(new Comment($validated));


        // $post->user->notify(new PostCommentedNotification(Auth::user()));
        broadcast(new TestPrivate(Auth::user()));

        broadcast(new PostCommentCreated(PostResource::make($post), PostCommentResource::make($comment)));

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => PostCommentResource::make($comment),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCommentRequest  $request
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCommentRequest $request, Post $post, Comment $comment)
    {
        $comment->update($request->validated());

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => PostCommentResource::make($comment)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post, Comment $comment)
    {
        if ($comment->comments()->count() > 0) {
            throw ValidationException::withMessages([
                'message' => 'Comment cannot be deleted, someone comment at this already'
            ])->status(Response::HTTP_FORBIDDEN);
        }

        if (!$post->user_id == Auth::user()->id) {
            throw ValidationException::withMessages([
                'message' => 'Comment cannot be deleted, someone comment at this already'
            ])->status(Response::HTTP_FORBIDDEN);
        }

        $comment->delete();

        broadcast(new PostCommentDeleted(PostResource::make($post), PostCommentResource::make($comment)));

        return response()->json([
            'message' => 'Comment deleted successfully!',
            'comment' => PostCommentResource::make($comment),
        ]);
    }
}
