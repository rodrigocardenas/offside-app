<div class="flex items-start space-x-3">
    <div class="flex-1">
        <div class="bg-offside-primary bg-opacity-40 rounded-lg p-3">
            <div class="font-medium text-sm">{{ $message->user->name }}</div>
            <div class="text-white">{{ $message->message }}</div>
            <div class="text-xs text-gray-400 mt-1">
                {{ @userTime($message->created_at) }}
            </div>
        </div>
    </div>
</div>
