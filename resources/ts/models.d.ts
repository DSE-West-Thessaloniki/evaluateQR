declare namespace App.Models {
    export interface DocumentGroup {
        id: number;
        name: string;
        step: number;
        published: boolean;
        created_at: string;
        updated_at: string;
        job_status: 0 | 1 | 2 | 3 | 4;
        job_status_text: string;
        job_date: string;
    }

    export interface Document {
        id: string;
        document_group_id: number;
        filename: string;
        state: number;
        created_at: string;
        updated_at: string;
    }
}
