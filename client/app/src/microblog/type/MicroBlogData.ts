import { MicroPostMap } from "./MicroPostMap";
import { MicroblogMember } from "./MicroBlogMember";


export interface MicroBlogData {
    user?: MicroblogMember,
    users?: {[id: string] : MicroblogMember},
    editingPostId?: string | null,    
    replyToPostId?: string | null,    
    postsLoading: boolean,
    savingPost: boolean,
    posts: MicroPostMap
}

