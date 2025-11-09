import {useState} from 'react';
import * as React from "react";

interface RecipeFormProps {
    onSubmit: (ingredients: string) => void;
    isLoading: boolean;
}

function RecipeForm({onSubmit, isLoading}: RecipeFormProps) {
    const [ingredients, setIngredients] = useState('');
    const [error, setError] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        // Validate ingredients
        const trimmed = ingredients.trim();
        if (!trimmed) {
            setError('Please enter at least one ingredient');
            return;
        }

        if (trimmed.length < 2) {
            setError('Ingredients must be at least 2 characters long');
            return;
        }

        if (trimmed.length > 3000) {
            setError('Ingredients list is too long (max 3000 characters)');
            return;
        }

        onSubmit(trimmed);
    };

    const exampleIngredients = [
        'chicken, rice, garlic, onion, tomatoes',
        'pasta, tomato sauce, mozzarella, basil',
        'eggs, flour, milk, sugar, vanilla',
        'salmon, lemon, dill, potatoes, butter',
    ];

    const handleExampleClick = (example: string) => {
        setIngredients(example);
        setError('');
    };

    return (
        <div className="bg-white rounded-lg shadow-lg p-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">
                AI Recipes Factory
            </h1>
            <p className="text-lg text-gray-600 mb-6">
                Enter your ingredients and let AI create amazing recipes for you!
            </p>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="ingredients" className="block text-sm font-medium text-gray-700 mb-2">
                        Your Ingredients
                    </label>
                    <textarea
                        id="ingredients"
                        value={ingredients}
                        onChange={(e) => setIngredients(e.target.value)}
                        placeholder="Enter ingredients separated by commas (e.g., chicken, rice, garlic, onion)"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        rows={4}
                        disabled={isLoading}
                    />
                    {error && (
                        <p className="mt-2 text-sm text-red-600">{error}</p>
                    )}
                    <p className="mt-2 text-sm text-gray-500">
                        Separate ingredients with commas. Be as specific or general as you like!
                    </p>
                </div>

                <button
                    type="submit"
                    disabled={isLoading}
                    className="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed cursor-pointer transition-colors"
                >
                    {isLoading ? 'Generating Recipe...' : 'Generate Recipe'}
                </button>
            </form>

            <div className="mt-6">
                <p className="text-sm font-medium text-gray-700 mb-2">Try an example:</p>
                <div className="flex flex-wrap gap-2">
                    {exampleIngredients.map((example, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => handleExampleClick(example)}
                            disabled={isLoading}
                            className="text-sm bg-gray-100 text-gray-700 px-3 py-1.5 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors"
                        >
                            {example}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default RecipeForm;
