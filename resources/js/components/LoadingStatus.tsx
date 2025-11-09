import type {RecipeRequestStatus} from '../types/recipe';

interface LoadingStatusProps {
    status: RecipeRequestStatus;
}

function LoadingStatus({status}: LoadingStatusProps) {
    const statusConfig = {
        PENDING: {
            title: 'Preparing your request...',
            description: 'Getting ready to create your recipe',
            color: 'blue',
        },
        PROCESSING: {
            title: 'AI Chef is cooking...',
            description: 'Creating your perfect recipe with artificial intelligence',
            color: 'purple',
        },
        COMPLETED: {
            title: 'Recipe ready!',
            description: 'Your recipe has been generated',
            color: 'green',
        },
        FAILED: {
            title: 'Something went wrong',
            description: 'Failed to generate recipe',
            color: 'red',
        },
    };

    const config = statusConfig[status];

    return (
        <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="text-center">
                <div className={`inline-flex items-center justify-center w-20 h-20 rounded-full bg-${config.color}-100 mb-4`}>
                    {status === 'FAILED' ? (
                        <svg className={`w-10 h-10 text-${config.color}-600`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    ) : (
                        <svg className={`w-10 h-10 text-${config.color}-600 animate-spin`} fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    )}
                </div>

                <h2 className={`text-2xl font-bold text-${config.color}-900 mb-2`}>
                    {config.title}
                </h2>
                <p className={`text-${config.color}-600 mb-6`}>
                    {config.description}
                </p>

                <div className="flex justify-center space-x-2">
                    <div className={`w-3 h-3 rounded-full bg-${config.color}-400 animate-bounce`} style={{animationDelay: '0ms'}}></div>
                    <div className={`w-3 h-3 rounded-full bg-${config.color}-400 animate-bounce`} style={{animationDelay: '150ms'}}></div>
                    <div className={`w-3 h-3 rounded-full bg-${config.color}-400 animate-bounce`} style={{animationDelay: '300ms'}}></div>
                </div>

                {status !== 'FAILED' && (
                    <p className="mt-6 text-sm text-gray-500">
                        This usually takes 10-30 seconds...
                    </p>
                )}
            </div>
        </div>
    );
}

export default LoadingStatus;
