import * as React from 'react';

interface Props { }

interface State {
    title: string
    content: string
}

export class MicroblogForm extends React.Component<Props, State>  {

    constructor(props: Props) {
        super(props)
    }

    render(): JSX.Element {
        return (<form className="MicroblogForm" onSubmit={(e: React.SyntheticEvent) => {
            console.log(this.state);
            e.preventDefault()
        }}>
            <div className="MicroblogForm__Title">
                <input name="title" onChange={(e: React.FormEvent<HTMLInputElement>) => {
                    const v = e.currentTarget.value;
                    this.setState({
                        title: v
                    })
                }} />
            </div>
            <div className="MicroblogForm__Content">
                <textarea name="content" onChange={(e: React.FormEvent<HTMLTextAreaElement>) => {
                    const v = e.currentTarget.value;
                    this.setState({
                        content: v
                    })
                }}></textarea>
            </div>

        </form>)
    }
}