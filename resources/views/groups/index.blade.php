<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Título de la sección -->
            <div class="flex items-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-xl font-semibold text-offside-light">Tus grupos</h2>
            </div>

            <!-- Lista de grupos -->
            <div class="space-y-4">
                @foreach($groups as $group)
                    <div class="bg-white bg-opacity-10 rounded-xl p-4 hover:bg-opacity-15 transition-all">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('groups.show', $group) }}" class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="flex -space-x-2">
                                        @foreach($group->users->take(4) as $user)
                                            <div class="w-8 h-8 rounded-full bg-offside-primary flex items-center justify-center text-xs font-bold ring-2 ring-offside-dark">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endforeach
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">{{ $group->name }}</h3>
                                        <p class="text-sm text-gray-400">hace {{ $group->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </a>
                            <div class="flex space-x-2 ml-4">
                                <button
                                    type="button"
                                    onclick="copyGroupCode('{{ $group->code }}', '{{ $group->name }}')"
                                    class="flex items-center space-x-1 text-offside-light hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                                    title="Copiar código para compartir manualmente">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-12a2 2 0 00-2-2h-2M8 5a2 2 0 002 2h4a2 2 0 002-2M8 5a2 2 0 012-2h4a2 2 0 012 2" />
                                    </svg>
                                    <span class="text-sm">Código</span>
                                </button>
                                <button
                                    type="button"
                                    onclick="showInviteModal('{{ $group->name }}', '{{ route('groups.invite', $group->code) }}')"
                                    class="flex items-center space-x-1 text-offside-light hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                                    title="Compartir grupo">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    <span class="text-sm">Compartir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Botón Crear Grupo -->
            <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
                <a href="{{ route('groups.create') }}" class="block w-full bg-gradient-to-r from-orange-500 to-orange-400 text-white text-center py-4 rounded-full font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                    + Crear grupo
                </a>
            </div>

            <!-- Unirse a grupo -->
            <div class="mt-4 text-center">
                <span class="text-gray-400">o</span>
                <button onclick="document.getElementById('joinGroupModal').classList.remove('hidden')" class="ml-2 text-offside-light hover:underline">
                    Unirse a un grupo
                </button>
            </div>

            <!-- Modal para unirse a grupo -->
            <div id="joinGroupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
                <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-xl font-semibold mb-4">Unirse a un grupo</h3>
                    <form action="{{ route('groups.join') }}" method="POST">
                        @csrf
                        <input type="text"
                               name="code"
                               placeholder="Código del grupo"
                               class="w-full bg-transparent border border-offside-primary rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-secondary mb-4">
                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                    onclick="document.getElementById('joinGroupModal').classList.add('hidden')"
                                    class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="bg-offside-secondary hover:bg-offside-primary px-4 py-2 rounded-lg transition-colors">
                                Unirse
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de invitación -->
            <div id="inviteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 z-50">
                <div class="bg-offside-dark rounded-xl p-6 w-full max-w-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-white">Compartir grupo</h3>
                        <button onclick="hideInviteModal()" class="text-gray-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Mensaje de invitación</label>
                            <textarea id="inviteMessage" rows="3" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary"></textarea>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="copyInviteText()" class="flex-1 flex items-center justify-center space-x-2 bg-offside-primary hover:bg-offside-primary-dark text-white px-4 py-2 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-12a2 2 0 00-2-2h-2M8 5a2 2 0 002 2h4a2 2 0 002-2M8 5a2 2 0 012-2h4a2 2 0 012 2" />
                                </svg>
                                <span>Copiar</span>
                            </button>
                            <button onclick="shareOnWhatsApp()" class="flex-1 flex items-center justify-center space-x-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span>WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentInviteLink = '';
        let currentGroupName = '';

        function showInviteModal(groupName, inviteLink) {
            currentInviteLink = inviteLink;
            currentGroupName = groupName;
            const defaultMessage = `¡Únete a mi grupo "${groupName}" en Offside Club! Haz clic aquí para unirte automáticamente: ${inviteLink}`;
            document.getElementById('inviteMessage').value = defaultMessage;
            document.getElementById('inviteModal').classList.remove('hidden');
        }

        function hideInviteModal() {
            document.getElementById('inviteModal').classList.add('hidden');
        }

        function copyInviteText() {
            const text = document.getElementById('inviteMessage').value;
            copyToClipboard(text, '¡Mensaje copiado al portapapeles!');
        }

        function shareOnWhatsApp() {
            const text = encodeURIComponent(document.getElementById('inviteMessage').value);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        function copyGroupCode(code, groupName) {
            const text = `¡Únete a mi grupo "${groupName}" en Offside Club! Usa este código: ${code}`;
            copyToClipboard(text, '¡Código copiado al portapapeles!');
        }

        function copyToClipboard(text, successMessage) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification(successMessage);
            }).catch(err => {
                console.error('Error al copiar al portapapeles:', err);
                showNotification('Error al copiar al portapapeles', 'error');
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed bottom-20 left-1/2 transform -translate-x-1/2 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 2000);
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideInviteModal();
            }
        });
    </script>
</x-app-layout>
