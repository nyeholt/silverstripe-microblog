import * as React from 'react';
import { Dispatch, AnyAction } from 'redux';
import { createPost, updatePost, editPost } from 'src/microblog/actions/MicroBlogActions';
import { connect } from 'react-redux';
import { MicroPost } from 'src/microblog/type/MicroPost';

import * as avatar from "assets/images/marcus.png";

interface Props {
    editPost?: MicroPost | null
    showTitle?: boolean
}

interface DispatchProps {
    create?: (content: string, properties: { [key: string]: any }, to?: { [key: string]: string }) => any
    update?: (content: string, properties: { [key: string]: any }) => any
    cancel?: () => any
}

interface State {
    id: string | null
    title: string
    content: string
}

class MicroblogForm extends React.Component<Props & DispatchProps, State>  {
    constructor(props: Props) {
        super(props)

        this.state = {
            id: null,
            title: "",
            content: ""
        }

    }

    newPost = (e: React.SyntheticEvent) => {
        e.preventDefault();
        const { editPost } = this.props;
        let properties: any = editPost ? editPost : {};
        properties.Title = this.state.title;

        if (this.state.content.length === 0) {
            return;
        }

        let updateFunc = this.props.editPost ? this.props.update : this.props.create;
        updateFunc ? updateFunc(this.state.content, properties) : null;

        this.setState({
            id: null,
            content: "",
            title: ""
        });
    }

    static getDerivedStateFromProps(props: Props & DispatchProps, state: State) {
        if (props.editPost && props.editPost.ID != state.id) {
            return {
                title: props.editPost.Title,
                content: props.editPost.Content,
                id: props.editPost.ID,
            }
        }
        if (!props.editPost && state.id) {
            return {
                id: null,
                content: "",
                title: ""
            };
        }
        return null;
    }

    render(): JSX.Element {
        const {
            showTitle } = this.props;

        const bgImage: React.CSSProperties = {
            display: "block",
            background: `transparent url(microblog/client/www/${avatar}) no-repeat`,
            backgroundSize: "32px",
            width: "32px",
            height: "32px",
            marginRight: "10px",
        };

        return (
            <div className="MicroblogForm">
                <div className="MicroblogForm__Profile">
                    <span role="img" className="MicroblogForm__Avatar" style={bgImage}>
                    </span>
                </div>
                <div className="MicroblogForm__Fields">
                    <form onSubmit={(e: React.SyntheticEvent) => {
                        e.preventDefault();
                        return false;
                    }}>

                        {showTitle &&
                            <div className="MicroblogForm__Field">
                                <input name="title" value={this.state.title} onChange={(e: React.FormEvent<HTMLInputElement>) => {
                                    const v = e.currentTarget.value;
                                    this.setState({
                                        title: v
                                    })
                                }} />
                            </div>
                        }
                        <div className="MicroblogForm__Field">
                            <textarea placeholder="Say something..." name="content" value={this.state.content} onChange={(e: React.FormEvent<HTMLTextAreaElement>) => {
                                const v = e.currentTarget.value;
                                this.setState({
                                    content: v
                                })
                            }}></textarea>
                        </div>
                        <div className="MicroblogForm__Actions">
                            <button className="MicroblogForm__Action__Default" onClick={this.newPost}>{this.props.editPost && "Update"}{!this.props.editPost && "Post"}</button>
                            <button onClick={() => {
                                this.setState({
                                    id: null,
                                    content: "",
                                    title: ""
                                })
                                this.props.cancel ? this.props.cancel() : null;
                            }}>Cancel</button>
                        </div>

                    </form>
                </div>
            </div>)
    }
}



const mapDispatchToProps = (dispatch: Dispatch<AnyAction>): DispatchProps => {
    return {
        create: (content: string, properties: { [key: string]: any }, to?: { [key: string]: string }) => dispatch(createPost(content, properties, to) as any),
        update: (content: string, properties: { [key: string]: any }) => dispatch(updatePost(content, properties) as any),
        cancel: () => dispatch(editPost(null))
    };
}

const ConnectedMicroblogForm = connect<DispatchProps>(null, mapDispatchToProps)(MicroblogForm);
export default ConnectedMicroblogForm;
