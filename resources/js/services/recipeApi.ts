import axios from 'axios';
import type {RecipeGenerateResponse, RecipeRequestResponse} from '../types/recipe';

const API_BASE = '/api/v1';

export const recipeApi = {

    async generateRecipe(ingredients: string): Promise<RecipeGenerateResponse> {
        const response = await axios.post<RecipeGenerateResponse>(
            `${API_BASE}/recipes/generate`,
            {ingredients}
        );

        return response.data;
    },

    async checkRequestStatus(requestId: string): Promise<RecipeRequestResponse> {
        const response = await axios.get<RecipeRequestResponse>(
            `${API_BASE}/recipes/requests/${requestId}`
        );

        return response.data;
    },

    async pollForRecipe(
        requestId: string,
        onStatusUpdate: (status: RecipeRequestResponse) => void,
        interval: number = 2000,
        maxAttempts: number = 60
    ): Promise<RecipeRequestResponse> {
        let attempts = 0;

        return new Promise((resolve, reject) => {
            const poll = async () => {
                try {
                    attempts++;
                    const response = await this.checkRequestStatus(requestId);

                    onStatusUpdate(response);

                    if (response.status === 'COMPLETED' || response.status === 'FAILED') {
                        resolve(response);
                        return;
                    }

                    if (attempts >= maxAttempts) {
                        reject(new Error('Polling timeout - recipe generation took too long'));
                        return;
                    }

                    setTimeout(poll, interval);
                } catch (error) {
                    reject(error);
                }
            };

            poll();
        });
    },
};
