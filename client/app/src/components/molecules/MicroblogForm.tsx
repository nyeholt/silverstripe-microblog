import * as React from 'react';
import { GlobalStore } from 'src/type/GlobalStore';
import { Dispatch, AnyAction } from 'redux';
import { createPost } from 'src/microblog/actions/MicroBlogActions';
import { connect } from 'react-redux';

interface Props {
    postId?: string
}

interface State {
    title: string
    content: string
}

class MicroblogForm extends React.Component<Props & DispatchProps, State>  {

    constructor(props: Props) {
        super(props)
        this.state = {
            title: "",
            content: ""
        }
    }

    newPost = (e: React.SyntheticEvent) => {
        e.preventDefault();
        let properties: any = {};
        if (this.state.title && this.state.title.length) {
            properties.Title = this.state.title;
        }
        this.props.create ? this.props.create(this.state.content, properties) : null;
        this.setState({
            content: "",
            title: ""
        });
    }

    render(): JSX.Element {
        return (<form className="MicroblogForm" onSubmit={this.newPost}>
            <div className="MicroblogForm__Title">
                <input name="title" value={this.state.title} onChange={(e: React.FormEvent<HTMLInputElement>) => {
                    const v = e.currentTarget.value;
                    this.setState({
                        title: v
                    })
                }} />
            </div>
            <div className="MicroblogForm__Content">
                <textarea name="content" value={this.state.content} onChange={(e: React.FormEvent<HTMLTextAreaElement>) => {
                    const v = e.currentTarget.value;
                    this.setState({
                        content: v
                    })
                }}></textarea>
            </div>
            <div className="MicroblogForm__Actions">
                <button>Post</button>
            </div>

        </form>)
    }
}

interface DispatchProps {
    create?: (content: string, properties: {[key: string]: any}, to?: {[key: string]: string}) => any
}


const mapStateToProps = (state: GlobalStore): Props => {
    return {
        
    }
}


const mapDispatchToProps = (dispatch: Dispatch<AnyAction>): DispatchProps => {
    return {
        create: (content: string, properties: {[key: string]: any}, to?: {[key: string]: string}) => dispatch(createPost(content, properties, to) as any),
    };
}


const ConnectedMicroblogForm = connect<Props & DispatchProps>(mapStateToProps, mapDispatchToProps)(MicroblogForm);
export default ConnectedMicroblogForm;
