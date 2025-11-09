import {useState} from 'react';
import {useNavigate} from 'react-router-dom';
import RecipeForm from '../components/RecipeForm';
import LoadingStatus from '../components/LoadingStatus';
import {recipeApi} from '../services/recipeApi';
import type {RecipeRequestStatus} from '../types/recipe';

type HomeState =
    | { type: 'form' }
    | { type: 'loading'; status: RecipeRequestStatus }
    | { type: 'error'; message: string };

function HomePage() {
    const navigate = useNavigate();
    const [state, setState] = useState<HomeState>({type: 'form'});

    const handleGenerateRecipe = async (ingredients: string) => {
        try {
            setState({type: 'loading', status: 'PENDING'});
            const generateResponse = await recipeApi.generateRecipe(ingredients);
            navigate(`/request/${generateResponse.requestId}`);
        } catch (error) {
            console.error('Error generating recipe:', error);
            setState({
                type: 'error',
                message: error instanceof Error ? error.message : 'An unexpected error occurred. Please try again.',
            });
        }
    };

    const handleTryAgain = () => {
        setState({type: 'form'});
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
            <div className="max-w-5xl mx-auto">
                {state.type === 'form' && (
                    <RecipeForm
                        onSubmit={handleGenerateRecipe}
                        isLoading={false}
                    />
                )}

                {state.type === 'loading' && (
                    <LoadingStatus status={state.status}/>
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
                                onClick={handleTryAgain}
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

export default HomePage;
