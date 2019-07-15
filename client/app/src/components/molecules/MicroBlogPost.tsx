import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogForm from 'src/components/molecules/MicroblogForm';

import { connect } from 'react-redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { Dispatch } from 'redux';
import { deletePost, editPost } from 'src/microblog/actions/MicroBlogActions';

interface Props {
    post: MicroPost
    showTitle?: boolean
}

interface StateProps {
    editId?: string | null
}

interface DispatchProps {
    onEdit?: (id: string) => void
    onDelete?: (postId: string) => void
}

const MicroBlogPost = ({ post, editId, showTitle, onEdit, onDelete }: Props & StateProps & DispatchProps): JSX.Element => {
    return <div className={post.ID == editId ? "Card Card--edited" : "Card"}>
        {showTitle && <div className="Card__Title">{post.Title}</div> }
        <div className="Card__Body">{post.Content}</div>
        {post.CanEdit == "1" && editId === post.ID && 
            <div className="Card__Edit">
                <MicroblogForm editPost={post} />
            </div>
        }

        {post.CanEdit == "1" &&
            <div className="Card__Actions">
                <button onClick={() => { onEdit ? onEdit(post.ID) : null; }}>Edit</button><button onClick={() => { onDelete ? onDelete(post.ID) : null; }}>Delete</button>
            </div>
        }

    </div>;
}


const mapStateToProps = (state: GlobalStore): StateProps => {
    return {
        editId: state.microblog.editingPostId
    }
}


const mapDispatchToProps = (dispatch: Dispatch): DispatchProps => {
    return {
        onEdit: (id: string) => dispatch(editPost(id)),
        onDelete: (postId: string) => dispatch(deletePost(postId) as any),
    };
}

const ConnectedMicroBlogPost = connect<StateProps>(mapStateToProps, mapDispatchToProps)(MicroBlogPost);
export default ConnectedMicroBlogPost;
