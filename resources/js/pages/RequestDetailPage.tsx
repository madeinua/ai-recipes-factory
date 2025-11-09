import {useState, useEffect} from 'react';
import {useParams, useNavigate} from 'react-router-dom';
import {recipeApi} from '../services/recipeApi';
import RecipeDisplay from '../components/RecipeDisplay';
import LoadingStatus from '../components/LoadingStatus';
import type {Recipe, RecipeRequestStatus} from '../types/recipe';

function RequestDetailPage() {
    const {id} = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [recipe, setRecipe] = useState<Recipe | null>(null);
    const [status, setStatus] = useState<RecipeRequestStatus>('PENDING');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!id) {
            setError('No request ID provided');
            setLoading(false);
            return;
        }

        let isMounted = true; // Prevent state updates if unmounted

        const fetchRequest = async () => {
            try {
                setLoading(true);
                setError(null);

                const response = await recipeApi.checkRequestStatus(id);

                if (!isMounted) return;

                setStatus(response.status);

                if (response.status === 'COMPLETED' && response.recipe) {
                    setRecipe(response.recipe);
                    setError(null);
                    setLoading(false);
                } else if (response.status === 'FAILED') {
                    setError(response.errorMessage || 'Recipe generation failed');
                    setLoading(false);
                } else {
                    setLoading(false);
                    await pollForCompletion(id);
                }
            } catch (err) {
                if (!isMounted) return;

                console.error('Error fetching request:', err);
                setError('Request not found or has expired');
                setLoading(false);
                setRecipe(null);
            }
        };

        const pollForCompletion = async (requestId: string) => {
            try {
                const result = await recipeApi.pollForRecipe(
                    requestId,
                    (statusUpdate) => {
                        if (!isMounted) return;
                        setStatus(statusUpdate.status);
                    }
                );

                if (!isMounted) return;

                if (result.status === 'COMPLETED' && result.recipe) {
                    setRecipe(result.recipe);
                } else if (result.status === 'FAILED') {
                    setError(result.errorMessage || 'Recipe generation failed');
                }
            } catch (err) {
                if (!isMounted) return;

                setError('Failed to complete recipe generation. Please try again.');
                console.error('Error polling request:', err);
            }
        };

        fetchRequest();

        return () => {
            isMounted = false;
        };
    }, [id]);

    const handleGenerateNew = () => {
        navigate('/');
    };

    if (!recipe && !error && (status === 'PENDING' || status === 'PROCESSING')) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
                <div className="max-w-5xl mx-auto">
                    <LoadingStatus status={status}/>
                </div>
            </div>
        );
    }

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
                <div className="max-w-5xl mx-auto">
                    <div className="bg-white rounded-lg shadow-lg p-8">
                        <div className="text-center">
                            <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                                <svg className="w-10 h-10 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-blue-900 mb-2">
                                Loading request...
                            </h2>
                            <p className="text-blue-600">
                                Please wait while we check your request
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (error || !recipe) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
                <div className="max-w-5xl mx-auto">
                    <div className="bg-white rounded-lg shadow-lg p-8">
                        <div className="text-center">
                            <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-4">
                                <svg className="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-red-900 mb-2">
                                {error || 'Request not found'}
                            </h2>
                            <p className="text-red-600 mb-6">
                                The request you're looking for doesn't exist or has expired.
                            </p>
                            <button
                                onClick={handleGenerateNew}
                                className="bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer transition-colors"
                            >
                                Go Home
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (recipe) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
                <div className="max-w-5xl mx-auto">
                    <RecipeDisplay recipe={recipe} onGenerateNew={handleGenerateNew}/>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
            <div className="max-w-5xl mx-auto">
                <div className="bg-white rounded-lg shadow-lg p-8">
                    <div className="text-center">
                        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                            <svg className="w-10 h-10 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h2 className="text-2xl font-bold text-gray-900 mb-2">
                            Loading...
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default RequestDetailPage;
