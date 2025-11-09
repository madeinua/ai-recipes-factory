import {useState} from 'react';
import type {Recipe} from '../types/recipe';

interface RecipeDisplayProps {
    recipe: Recipe;
    onGenerateNew: () => void;
}

function RecipeDisplay({recipe, onGenerateNew}: RecipeDisplayProps) {
    const [copied, setCopied] = useState(false);

    const handleShare = async () => {
        const url = window.location.href;

        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy URL:', err);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="flex justify-between items-start mb-6">
                <div className="flex-1">
                    <h1 className="text-4xl font-bold text-gray-900 mb-2">
                        {recipe.title}
                    </h1>
                    <p className="text-lg text-gray-600">
                        {recipe.excerpt}
                    </p>
                </div>
                <div className="flex gap-2 ml-4">
                    <button
                        onClick={handleShare}
                        className="bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 cursor-pointer transition-colors flex items-center gap-2"
                    >
                        {copied ? (
                            <>
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7"/>
                                </svg>
                                Copied!
                            </>
                        ) : (
                            <>
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                Share
                            </>
                        )}
                    </button>
                    <button
                        onClick={onGenerateNew}
                        className="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer transition-colors"
                    >
                        New Recipe
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div className="bg-blue-50 rounded-lg p-4">
                    <div className="flex items-center">
                        <svg className="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <div>
                            <p className="text-sm text-blue-600 font-medium">Servings</p>
                            <p className="text-xl font-bold text-blue-900">{recipe.numberOfPersons || '-'}</p>
                        </div>
                    </div>
                </div>

                <div className="bg-green-50 rounded-lg p-4">
                    <div className="flex items-center">
                        <svg className="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p className="text-sm text-green-600 font-medium">Prep Time</p>
                            <p className="text-xl font-bold text-green-900">
                                {recipe.timeToPrepare ? `${recipe.timeToPrepare} min` : '-'}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="bg-orange-50 rounded-lg p-4">
                    <div className="flex items-center">
                        <svg className="w-6 h-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        <div>
                            <p className="text-sm text-orange-600 font-medium">Cook Time</p>
                            <p className="text-xl font-bold text-orange-900">
                                {recipe.timeToCook ? `${recipe.timeToCook} min` : '-'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 mb-4">Ingredients</h2>
                    <ul className="space-y-2">
                        {recipe.ingredients.map((ingredient, index) => (
                            <li key={index} className="flex items-start">
                                <svg className="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"/>
                                </svg>
                                <span className="text-gray-700">
                                    {ingredient.value > 0 ? (
                                        <>
                                            <span className="font-semibold">{ingredient.value}</span>
                                            {ingredient.measure && ` ${ingredient.measure}`}
                                            {' '}{ingredient.name}
                                        </>
                                    ) : (
                                        ingredient.name
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div>
                    <h2 className="text-2xl font-bold text-gray-900 mb-4">Instructions</h2>
                    <ol className="space-y-4">
                        {recipe.instructions.map((instruction, index) => (
                            <li key={index} className="flex">
                                <span className="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">
                                    {index + 1}
                                </span>
                                <p className="text-gray-700 pt-1">{instruction}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </div>
        </div>
    );
}

export default RecipeDisplay;
