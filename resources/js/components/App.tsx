import {useState} from 'react';
import RecipeForm from './RecipeForm';
import RecipeDisplay from './RecipeDisplay';
import LoadingStatus from './LoadingStatus';
import {recipeApi} from '../services/recipeApi';
import type {Recipe, RecipeRequestStatus} from '../types/recipe';

type AppState =
    | { type: 'form' }
    | { type: 'loading'; status: RecipeRequestStatus }
    | { type: 'recipe'; recipe: Recipe }
    | { type: 'error'; message: string };

function App() {
    const [state, setState] = useState<AppState>({type: 'form'});

    const handleGenerateRecipe = async (ingredients: string) => {
        try {
            setState({type: 'loading', status: 'PENDING'});

            const generateResponse = await recipeApi.generateRecipe(ingredients);

            setState({type: 'loading', status: generateResponse.status});

            const result = await recipeApi.pollForRecipe(
                generateResponse.requestId,
                (statusUpdate) => {
                    setState({type: 'loading', status: statusUpdate.status});
                }
            );

            if (result.status === 'COMPLETED' && result.recipe) {
                setState({type: 'recipe', recipe: result.recipe});
            } else if (result.status === 'FAILED') {
                setState({
                    type: 'error',
                    message: result.errorMessage || 'Failed to generate recipe. Please try again.',
                });
            }
        } catch (error) {
            console.error('Error generating recipe:', error);
            setState({
                type: 'error',
                message: error instanceof Error ? error.message : 'An unexpected error occurred. Please try again.',
            });
        }
    };

    const handleGenerateNew = () => {
        setState({type: 'form'});
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
            <div className="max-w-5xl mx-auto">
                {state.type === 'loading' && (
                    <LoadingStatus status={state.status}/>
                )}

                {state.type === 'form' && (
                    <RecipeForm
                        onSubmit={handleGenerateRecipe}
                        isLoading={false}
                    />
                )}

                {state.type === 'recipe' && (
                    <RecipeDisplay
                        recipe={state.recipe}
                        onGenerateNew={handleGenerateNew}
                    />
                )}

                {state.type === 'error' && (
                    <div className="bg-white rounded-lg shadow-lg p-8">
                        <div className="text-center">
                            <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-4">
                                <svg className="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-red-900 mb-2">
                                Oops! Something went wrong
                            </h2>
                            <p className="text-red-600 mb-6">
                                {state.message}
                            </p>
                            <button
                                onClick={handleGenerateNew}
                                className="bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer transition-colors"
                            >
                                Try Again
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

export default App;
