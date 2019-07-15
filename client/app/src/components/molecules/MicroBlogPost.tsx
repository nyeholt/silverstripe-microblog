import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import { connect } from 'react-redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { Dispatch } from 'redux';
import { ActionType } from 'src/type/Actions';
import { deletePost } from 'src/microblog/actions/MicroBlogActions';

interface Props {
    post: MicroPost
}

interface StateProps {
    editId?: string
}

interface DispatchProps {
    onEdit?: (id: string) => void
    onDelete?: (postId: string) => void
}

const MicroBlogPost = ({ post, editId, onEdit, onDelete }: Props & StateProps & DispatchProps): JSX.Element => {
    return <div className={post.ID == editId ? "Card Card--edited" : "Card"}>
        <div className="Card__Title">{post.Title}</div>
        <div className="Card__Body">{post.Content}</div>
        {post.CanEdit &&
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
        onEdit: (id: string) => dispatch({
            type: ActionType.EDIT_POST,
            postId: id,
        }),
        onDelete: (postId: string) => dispatch(deletePost(postId) as any),
    };
}

const ConnectedMicroBlogPost = connect<StateProps>(mapStateToProps, mapDispatchToProps)(MicroBlogPost);
export default ConnectedMicroBlogPost;
