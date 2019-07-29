import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogForm from 'src/components/molecules/MicroblogForm';

import { connect } from 'react-redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { Dispatch } from 'redux';
import { deletePost, editPost, replyToPost, loadPosts, votePost } from 'src/microblog/actions/MicroBlogActions';
import MicroblogPostList from './MicroblogPostList';
import { MicroblogMember } from 'src/microblog/type/MicroBlogMember';

import * as ReactMarkdown from 'react-markdown';

interface Props {
    post: MicroPost
    showTitle?: boolean
}

interface StateProps {
    editId?: string | null
    replyId?: string | null
    allPosts?: MicroPost[]
    user?: MicroblogMember
}

interface DispatchProps {
    votePost?: (postId: string, dir: number) => any
    onEdit?: (id: string) => void
    onReply?: (id: string) => void
    onDelete?: (postId: string) => void
    loadChildren?: () => void
}

const MicroBlogPost = (props: Props & StateProps & DispatchProps): JSX.Element => {
    const {
        post,
        allPosts,
        replyId,
        editId,
        showTitle,
        onEdit,
        onDelete,
        onReply,
        loadChildren,
        votePost,
        user
    } = props;
    const children = allPosts ? allPosts.filter(child => child.ParentID == post.ID) : [];
    const expectedChildren = parseInt(post.NumChildren);

    let score = parseInt(post.Up) - parseInt(post.Down);
    if (isNaN(score)) {
        score = 0;
    }

    const target = post.ParentID == "0" && post.TargetInfo ? JSON.parse(post.TargetInfo) : {};

    // note that below we use "escapeHtml=false" because we've run the 
    // raw content through HTML Purifier

    const confirmDelete = (e: React.SyntheticEvent) => {
        if (confirm("Delete this post?")) {
            onDelete ? onDelete(post.ID) : null;
        }
    }

    return (
        <div className={post.ID == editId ? "MicroBlogPost MicroBlogPost--edited" : "MicroBlogPost"}>
            <div className="MicroBlogPost__Profile">
                <span className="MicroBlogPost__Avatar"></span>
                <button className="MicroBlogPost__VoteArrow" onClick={() => votePost ? votePost(post.ID, 1) : null}>⇧</button>
                <span className="MicroBlogPost__PostScore">{score}</span>
                <button className="MicroBlogPost__VoteArrow" onClick={() => votePost ? votePost(post.ID, -1) : null}>⇩</button>
            </div>
            <div className="MicroBlogPost__Post">
                <div className="MicroBlogPost__Author">
                    <strong className="MicroBlogPost__Author__Name">{post.Author}</strong>
                    <span className="MicroBlogPost__Author__Created">{post.Created}</span>
                </div>
                {showTitle && <div className="MicroBlogPost__Title">{post.Title}</div>}
                <div className="MicroBlogPost__Content"><ReactMarkdown escapeHtml={false} source={post.Content}></ReactMarkdown></div>

                {post.CanEdit == "1" && editId === post.ID &&
                    <div className="MicroBlogPost__EditPost">
                        <MicroblogForm editPost={post} />
                    </div>
                }

                {replyId === post.ID &&
                    <div className="MicroBlogPost__ReplyPost">
                        <MicroblogForm extraProperties={{ ParentID: replyId }} />
                    </div>
                }

                {target.Title &&
                    <div className="MicroBlogPost__Target">
                        <span>Comment on <a href={target.Link}>{target.Title}</a></span>
                    </div>
                }

                <div className="MicroBlogPost__Actions">
                    <a href={'/microblog/show/' + post.ID}>Link</a>
                    {user &&
                        <button onClick={() => { onReply ? onReply(post.ID) : null; }}>Reply</button>
                    }
                    {post.CanEdit == "1" && <React.Fragment>
                        <button onClick={() => { onEdit ? onEdit(post.ID) : null; }}>Edit</button>
                        <button onClick={confirmDelete}>Delete</button>
                    </React.Fragment>
                    }
                </div>

                {
                    expectedChildren > 0 && <MicroblogPostList loadChildren={loadChildren} posts={children} expectedCount={parseInt(post.NumChildren)} />
                }
            </div>
        </div>
    );
}


const mapStateToProps = (state: GlobalStore): StateProps => {
    let allPosts: MicroPost[] = [];
    for (var id in state.microblog.posts) {
        allPosts.push(state.microblog.posts[id]);
    }
    return {
        allPosts: allPosts,
        editId: state.microblog.editingPostId,
        replyId: state.microblog.replyToPostId,
        user: state.microblog.user
    }
}

const mapDispatchToProps = (dispatch: Dispatch, ownProps: Props): DispatchProps => {
    return {
        onEdit: (id: string) => dispatch(editPost(id)),
        onDelete: (postId: string) => dispatch(deletePost(postId) as any),
        onReply: (id: string) => dispatch(replyToPost(id)),
        loadChildren: () => dispatch(loadPosts("ParentID=" + ownProps.post.ID) as any),
        votePost: (postId: string, dir: number) => { return dispatch(votePost(postId, dir) as any) }
    };
}

const ConnectedMicroBlogPost = connect<StateProps>(mapStateToProps, mapDispatchToProps)(MicroBlogPost);
export default ConnectedMicroBlogPost;
