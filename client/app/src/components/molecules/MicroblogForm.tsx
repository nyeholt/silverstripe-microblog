import * as React from 'react';
import { Dispatch, AnyAction } from 'redux';
import { createPost, updatePost, editPost, replyToPost } from 'src/microblog/actions/MicroBlogActions';
import { connect } from 'react-redux';
import { MicroPost } from 'src/microblog/type/MicroPost';

import Dropzone from 'react-dropzone-uploader'


import * as avatar from "assets/images/marcus.png";
import { GlobalStore } from 'src/type/GlobalStore';

interface Props {
    target?: string | null
    editPost?: MicroPost | null
    extraProperties?: { [key: string]: string }    // extra parameters to be sent through
    showTitle?: boolean
}

interface DispatchProps {
    create?: (content: string, properties: { [key: string]: any }, to?: { [key: string]: string }) => any
    update?: (content: string, properties: { [key: string]: any }) => any
    cancel?: () => any
}

interface StateProps {
    savingPost: boolean
}

interface State {
    id: string | null
    title: string
    content: string
}

class MicroblogForm extends React.Component<Props & DispatchProps & StateProps, State>  {
    constructor(props: Props & StateProps) {
        super(props)

        this.state = {
            id: null,
            title: "",
            content: ""
        }

    }

    newPost = (e: React.SyntheticEvent) => {
        e.preventDefault();
        const { editPost, extraProperties, target } = this.props;
        let properties: any = editPost ? editPost : (extraProperties ? extraProperties : {});
        properties.Title = this.state.title;
        properties.target = target;

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
                content: props.editPost.OriginalContent ? props.editPost.OriginalContent : props.editPost.Content,
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

    getUploadParams = (meta: any) => {
        return {
            url: '/api/v1/microblog/upload',
        }
    }

    // called every time a file's `status` changes
    handleChangeStatus = (params: any, status: any) => {
        const { xhr } = params;
        if (status === 'done') {
            params.remove();
            const upload = JSON.parse(xhr.responseText);
            if (upload && upload.status == 200) {
                let currentContent = this.state.content;
                const link = upload.payload.MediaLink; // (upload.payload.Type == 'image' ? '!' : '') + '[' + upload.payload.Title + '](' + upload.payload.Link + ')';
                this.setState({
                    content: currentContent + (currentContent.length > 0 ? "\n" : "") + link
                })
            }
        }
    }

    // receives array of files that are done uploading when submit button is clicked
    handleSubmit = (files: any, allFiles: any) => {
        allFiles.forEach((f: any) => f.remove())
    }

    render(): JSX.Element {
        const {
            showTitle,
            extraProperties,
            savingPost
        } = this.props;

        const bgImage: React.CSSProperties = {
            background: `transparent url(microblog/client/www/${avatar}) no-repeat`,
        };

        const placeholder = extraProperties && extraProperties.ParentID ? "Reply..." : "Say something...";

        const buttonLabel = savingPost ? "Saving..." : (this.props.editPost ? "Update" : "Post");

        const dropzoneProps = {
            getUploadParams: this.getUploadParams,
            onChangeStatus: this.handleChangeStatus,
            // onSubmit: this.handleSubmit,
            accept: "image/*,audio/*,video/*",
            multiple: true,
            minSizeBytes: 0,
            maxSizeBytes: 10000000,
            maxFiles: 10,
            canCancel: true,
            canRemove: true,
            canRestart: false,
            styles: {},
            submitButtonContent: '',
            submitButtonDisabled: true,
            inputContent: '⭱ Upload',
            // classNames: { inputLabelWithFiles: defaultClassNames.inputLabel },
        };

        return (
            <React.Fragment>
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
                                <textarea placeholder={placeholder} name="content" value={this.state.content} onChange={(e: React.FormEvent<HTMLTextAreaElement>) => {
                                    const v = e.currentTarget.value;
                                    this.setState({
                                        content: v
                                    })
                                }}></textarea>
                            </div>
                            <div className="MicroblogForm__Actions">
                                <button disabled={savingPost} className="MicroblogForm__Action__Default" onClick={this.newPost}>
                                    {buttonLabel}
                                </button>
                                <button onClick={() => {
                                    this.setState({
                                        id: null,
                                        content: "",
                                        title: ""
                                    })
                                    this.props.cancel ? this.props.cancel() : null;
                                }}>Cancel</button>
                                <div className="MicroblogForm__Files">
                                    <span className="sr-only">Select or drag files to upload</span>
                                    <Dropzone
                                        {...dropzoneProps}
                                    ></Dropzone>
                                    {/* {...dropzoneProps} /> */}
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

            </React.Fragment>
        )
    }
}

const mapStateToProps = (state: GlobalStore): StateProps => {
    return {
        savingPost: state.microblog.savingPost
    }
}


const mapDispatchToProps = (dispatch: Dispatch<AnyAction>): DispatchProps => {
    return {
        create: (content: string, properties: { [key: string]: any }, to?: { [key: string]: string }) => dispatch(createPost(content, properties, to) as any),
        update: (content: string, properties: { [key: string]: any }) => dispatch(updatePost(content, properties) as any),
        cancel: () => { dispatch(editPost(null)); dispatch(replyToPost(null)); },
    };
}

const ConnectedMicroblogForm = connect<DispatchProps & StateProps>(mapStateToProps, mapDispatchToProps)(MicroblogForm);
export default ConnectedMicroblogForm;
