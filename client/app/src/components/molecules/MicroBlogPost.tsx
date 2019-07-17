import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogForm from 'src/components/molecules/MicroblogForm';

import { connect } from 'react-redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { Dispatch } from 'redux';
import { deletePost, editPost, replyToPost, loadPosts } from 'src/microblog/actions/MicroBlogActions';
import MicroblogPostList from './MicroblogPostList';

interface Props {
    post: MicroPost
    showTitle?: boolean
}

interface StateProps {
    editId?: string | null
    replyId?: string | null
    allPosts?: MicroPost[]
}

interface DispatchProps {
    onEdit?: (id: string) => void
    onReply?: (id: string) => void
    onDelete?: (postId: string) => void
    loadChildren?: () => void
}

const MicroBlogPost = ({ post, allPosts, replyId, editId, showTitle, onEdit, onDelete, onReply, loadChildren }: Props & StateProps & DispatchProps): JSX.Element => {
    const children = allPosts ? allPosts.filter(child => child.ParentID == post.ID) : [];
    const expectedChildren = parseInt(post.NumChildren);
    return (
        <div className={post.ID == editId ? "MicroBlogPost MicroBlogPost--edited" : "MicroBlogPost"}>
            <div className="MicroBlogPost__Profile"><span className="MicroBlogPost__Avatar"></span></div>
            <div className="MicroBlogPost__Post">
                <div className="MicroBlogPost__Author">
                    <strong className="MicroBlogPost__Author__Name">{post.Author}</strong>
                    <span className="MicroBlogPost__Author__Created">{post.Created}</span>
                </div>
                {showTitle && <div className="MicroBlogPost__Title">{post.Title}</div>}
                <div className="MicroBlogPost__Content">{post.Content}</div>

                {post.CanEdit == "1" && editId === post.ID &&
                    <div className="MicroBlogPost__EditPost">
                        <MicroblogForm editPost={post} />
                    </div>
                }

                {replyId === post.ID &&
                    <div className="MicroBlogPost__EditPost">
                        <MicroblogForm extraProperties={{ParentID: replyId}} />
                    </div>
                }

                {post.CanEdit == "1" &&
                    <div className="MicroBlogPost__Actions">
                        <button onClick={() => { onReply ? onReply(post.ID) : null; }}>Reply</button>
                        <button onClick={() => { onEdit ? onEdit(post.ID) : null; }}>Edit</button>
                        <button onClick={() => { onDelete ? onDelete(post.ID) : null; }}>Delete</button>
                    </div>
                }

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
        replyId: state.microblog.replyToPostId
    }
}

const mapDispatchToProps = (dispatch: Dispatch, ownProps: Props): DispatchProps => {
    return {
        onEdit: (id: string) => dispatch(editPost(id)),
        onDelete: (postId: string) => dispatch(deletePost(postId) as any),
        onReply: (id: string) => dispatch(replyToPost(id)),
        loadChildren: () => dispatch(loadPosts("ParentID=" + ownProps.post.ID) as any),
    };
}

const ConnectedMicroBlogPost = connect<StateProps>(mapStateToProps, mapDispatchToProps)(MicroBlogPost);
export default ConnectedMicroBlogPost;
