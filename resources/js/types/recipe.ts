export interface Recipe {
    id: string;
    title: string;
    excerpt: string;
    instructions: string[];
    ingredients: Ingredient[];
    numberOfPersons: number;
    timeToCook: number;
    timeToPrepare: number;
}

export interface Ingredient {
    name: string;
    value: number;
    measure: string;
}

export type RecipeRequestStatus = 'PENDING' | 'PROCESSING' | 'COMPLETED' | 'FAILED';

export interface RecipeGenerateResponse {
    requestId: string;
    status: RecipeRequestStatus;
    deduped: boolean;
    location: string;
}

export interface RecipeRequestResponse {
    id: string;
    status: RecipeRequestStatus;
    recipe?: Recipe;
    errorMessage?: string;
}
